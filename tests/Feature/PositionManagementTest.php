<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionManagementTest extends TestCase
{
    use RefreshDatabase;

    private function setupEmployee(): array
    {
        $user = User::factory()->create();
        $user->role = 'hr';
        $user->save();
        $this->actingAs($user);
        $branch = Branch::create(['branch_code' => 'HQ', 'name' => 'Head Office']);
        $department = Department::create(['branch_id' => $branch->id, 'name' => 'HR']);
        $designation = Designation::create(['name' => 'HR Executive', 'is_active' => true]);
        $employee = Employee::create(['employee_id' => 'EMP100', 'name' => 'Test Employee', 'first_name' => 'Test', 'last_name' => 'Employee',
            'branch_id' => $branch->id, 'department_id' => $department->id, 'joined_date' => '2020-01-01', 'employment_type' => 'Permanent', 'status' => 'Active']);

        return [$employee, ['branch_id' => $branch->id, 'department_id' => $department->id, 'designation_id' => $designation->id,
            'manager_id' => null, 'start_date' => '2020-01-01', 'change_reason' => 'Initial appointment']];
    }

    public function test_hr_creates_edits_and_retires_designations_without_deleting_history(): void
    {
        [$employee, $period] = $this->setupEmployee();
        $this->post(route('hr.person.positions.store'), ['name' => '  Accountant  ', 'is_active' => 1])->assertRedirect(route('hr.person.position-management'));
        $position = Designation::where('name', 'Accountant')->firstOrFail();
        $period['designation_id'] = $position->id;
        $this->postJson(route('hr.person.history.store', $employee), $period)->assertCreated();
        $this->put(route('hr.person.positions.update', $position), ['name' => 'Senior Accountant', 'is_active' => 0])->assertRedirect();
        $this->assertFalse($position->fresh()->is_active);
        $this->assertDatabaseHas('employment_history', ['employee_id' => $employee->id, 'designation_id' => $position->id]);
        $this->withoutVite()->get(route('hr.person.position-management', ['search' => 'Accountant', 'status' => 'inactive']))->assertOk()->assertSee('Senior Accountant')->assertSee('Inactive')->assertViewHas('positions', fn ($positions) => $positions->count() === 1 && $positions->first()->id === $position->id);
        $this->getJson(route('hr.person.lookup', ['employee_id' => 'EMP100']))->assertOk()->assertJsonPath('employee.current_designation', 'Senior Accountant');
        $this->get(route('hr.person.create', ['employee' => $employee->id]))->assertOk()->assertSee('id="employmentPeriodForm"', false)->assertSee('Senior Accountant (inactive)');
        $this->put(route('hr.person.positions.update', $position), ['name' => 'Senior Accountant', 'is_active' => 1])->assertRedirect();
        $this->assertTrue($position->fresh()->is_active);
    }

    public function test_unused_positions_can_be_deleted_but_history_and_permissions_are_protected(): void
    {
        [$employee, $period] = $this->setupEmployee();
        $unused = Designation::create(['name' => 'Unused title', 'is_active' => true]);
        $this->delete(route('hr.person.positions.destroy', $unused))->assertRedirect(route('hr.person.position-management'));
        $this->assertDatabaseMissing('designations', ['id' => $unused->id]);
        $this->postJson(route('hr.person.history.store', $employee), $period)->assertCreated();
        $url = route('hr.person.positions.destroy', $period['designation_id']);
        $this->deleteJson($url)->assertUnprocessable()->assertJsonValidationErrors('designation');
        $this->assertDatabaseHas('designations', ['id' => $period['designation_id']]);
        $this->assertDatabaseCount('employment_history', 1);
        $this->actingAs(User::factory()->create())->deleteJson($url)->assertForbidden();
    }

    public function test_duplicate_and_empty_names_are_rejected(): void
    {
        $this->setupEmployee();
        $url = route('hr.person.positions.store');
        $this->postJson($url, ['name' => 'HR Executive', 'is_active' => 1])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson($url, ['name' => '   ', 'is_active' => 1])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson($url, ['name' => 'Officer', 'is_active' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors('is_active');
        $this->assertDatabaseCount('designations', 1);
    }

    public function test_manager_only_change_retains_old_snapshot_and_can_keep_retired_current_designation(): void
    {
        [$employee, $period] = $this->setupEmployee();
        $manager = $employee->replicate();
        $manager->employee_id = 'MGR100';
        $manager->save();
        $this->postJson(route('hr.person.history.store', $employee), $period)->assertCreated();
        Designation::findOrFail($period['designation_id'])->update(['is_active' => false]);
        $this->postJson(route('hr.person.history.transfer', $employee), array_merge($period, [
            'manager_id' => $manager->id, 'start_date' => '2021-01-01', 'change_reason' => 'Manager reassignment',
        ]))->assertCreated();
        $history = $employee->employmentHistories()->orderBy('start_date')->get();
        $this->assertCount(2, $history);
        $this->assertNull($history[0]->manager_id);
        $this->assertSame('2020-12-31', $history[0]->end_date->toDateString());
        $this->assertSame($manager->id, $history[1]->manager_id);
        $this->assertSame($manager->id, $employee->fresh()->line_manager_id);
    }

    public function test_transfer_updates_branch_department_and_basic_save_cannot_bypass_history(): void
    {
        [$employee, $period] = $this->setupEmployee();
        $this->postJson(route('hr.person.history.store', $employee), $period)->assertCreated();
        $branch = Branch::create(['branch_code' => 'NEW', 'name' => 'New Branch']);
        $department = Department::create(['branch_id' => $branch->id, 'name' => 'Operations']);
        $this->getJson(route('hr.person.lookup', ['employee_id' => 'EMP100']))->assertOk();
        $basic = ['section' => 'basic', 'record_id' => $employee->id, 'employee_id' => 'EMP100', 'name' => 'Test Employee',
            'branch_id' => $branch->id, 'department_id' => $department->id, 'joined_date' => '2020-01-01', 'employment_type' => 'Permanent', 'allow_self_service' => 0];
        $this->postJson(route('hr.person.sections.store'), $basic)->assertUnprocessable()->assertJsonValidationErrors('department_id');
        $this->postJson(route('hr.person.history.transfer', $employee), array_merge($period, ['branch_id' => $branch->id,
            'department_id' => $department->id, 'start_date' => '2021-01-01', 'change_reason' => 'Branch transfer']))->assertCreated();
        $this->assertSame('NEW', $employee->fresh()->branch_code);
        $this->assertSame($department->id, $employee->fresh()->department_id);
        $this->assertSame($period['department_id'], $employee->employmentHistories()->oldest('start_date')->first()->department_id);
        $this->postJson(route('hr.person.sections.store'), $basic)->assertOk();
        $basic['joined_date'] = '2022-01-01';
        $this->postJson(route('hr.person.sections.store'), $basic)->assertUnprocessable()->assertJsonValidationErrors('joined_date');
    }

    public function test_reporting_cycles_and_resigned_managers_or_employees_are_rejected(): void
    {
        [$employee, $period] = $this->setupEmployee();
        $manager = $employee->replicate();
        $manager->employee_id = 'MGR100';
        $manager->line_manager_id = $employee->id;
        $manager->save();
        $url = route('hr.person.history.store', $employee);
        $this->postJson($url, array_merge($period, ['manager_id' => $manager->id]))->assertUnprocessable()->assertJsonValidationErrors('manager_id');
        $manager->update(['status' => 'Resigned', 'line_manager_id' => null]);
        $this->postJson($url, array_merge($period, ['manager_id' => $manager->id]))->assertUnprocessable()->assertJsonValidationErrors('manager_id');
        $employee->update(['status' => 'Resigned']);
        $this->postJson($url, $period)->assertUnprocessable()->assertJsonValidationErrors('employee');
        $this->assertDatabaseCount('employment_history', 0);
    }

    public function test_position_and_history_writes_require_hr_or_admin(): void
    {
        [$employee, $period] = $this->setupEmployee();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('hr.person.positions.store'), ['name' => 'Manager', 'is_active' => 1])->assertForbidden();
        $this->putJson(route('hr.person.positions.update', $period['designation_id']), ['name' => 'Manager', 'is_active' => 0])->assertForbidden();
        $this->postJson(route('hr.person.history.store', $employee), $period)->assertForbidden();
        $this->postJson(route('hr.person.history.transfer', $employee), $period)->assertForbidden();
        $this->withoutVite()->get(route('hr.person.create'))->assertOk()->assertDontSee('id="employmentPeriodForm"', false);
        $this->get(route('hr.person.position-management'))->assertOk()->assertDontSee('Create designation');
    }
}
