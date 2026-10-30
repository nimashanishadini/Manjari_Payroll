<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmploymentHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function employeeData(): array
    {
        $user = User::factory()->create();
        $user->role = 'hr';
        $user->save();
        $this->actingAs($user);
        $branch = Branch::create(['branch_code' => 'HQ', 'name' => 'HQ']);
        $department = Department::create(['branch_id' => $branch->id, 'name' => 'HR']);
        $designation = Designation::create(['name' => 'Officer', 'is_active' => true]);
        $employee = Employee::create(['employee_id' => 'EMP001', 'first_name' => 'Test', 'last_name' => 'Employee',
            'branch_id' => $branch->id, 'department_id' => $department->id, 'joined_date' => '2020-01-01', 'employment_type' => 'Permanent']);

        return [$employee, ['branch_id' => $branch->id, 'department_id' => $department->id,
            'designation_id' => $designation->id, 'manager_id' => null, 'start_date' => '2020-01-01', 'change_reason' => 'Appointment']];
    }

    public function test_transfer_closes_previous_period_and_preserves_manager_snapshot(): void
    {
        [$employee, $data] = $this->employeeData();
        $manager = $employee->replicate();
        $manager->employee_id = 'MANAGER';
        $manager->save();
        $data['manager_id'] = $manager->id;
        $this->postJson(route('hr.person.history.store', $employee), $data)->assertCreated();
        $designation = Designation::create(['name' => 'Senior Officer', 'is_active' => true]);
        $this->postJson(route('hr.person.history.transfer', $employee), array_merge($data, [
            'designation_id' => $designation->id, 'manager_id' => null, 'start_date' => '2021-01-01', 'change_reason' => 'Promotion',
        ]))->assertCreated();
        $history = $employee->employmentHistories()->orderBy('start_date')->get();
        $this->assertCount(2, $history);
        $this->assertSame('2020-12-31', $history[0]->end_date->toDateString());
        $this->assertSame($manager->id, $history[0]->manager_id);
        $this->assertNull($history[1]->end_date);
        $this->assertNull($employee->fresh()->line_manager_id);
        $employee->update(['line_manager_id' => $manager->id]);
        $this->assertNull($history[1]->fresh()->manager_id);
        $this->getJson(route('hr.person.history.index', $employee))->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_invalid_periods_and_duplicate_current_periods_are_rejected(): void
    {
        [$employee, $data] = $this->employeeData();
        $url = route('hr.person.history.store', $employee);
        $this->postJson($url, $data)->assertCreated();
        $this->postJson($url, $data)->assertUnprocessable();
        $this->postJson($url, $data + ['end_date' => '2020-02-01'])->assertUnprocessable();
        $this->postJson($url, array_merge($data, ['start_date' => '2019-01-01']))->assertUnprocessable()->assertJsonValidationErrors('start_date');
        $this->postJson($url, array_merge($data, ['start_date' => now()->addDay()->toDateString()]))->assertUnprocessable()->assertJsonValidationErrors('start_date');
        $this->postJson($url, array_merge($data, ['start_date' => '2020-02-01', 'end_date' => '2020-01-01']))->assertUnprocessable()->assertJsonValidationErrors('end_date');
        $this->assertDatabaseCount('employment_history', 1);
    }

    public function test_historical_period_is_retained_and_does_not_replace_current_employee_details(): void
    {
        [$employee, $data] = $this->employeeData();
        $url = route('hr.person.history.store', $employee);
        $this->postJson($url, $data + ['end_date' => '2020-12-31'])->assertCreated();
        $other = Department::create(['branch_id' => $employee->branch_id, 'name' => 'Operations']);
        $this->postJson($url, array_merge($data, ['department_id' => $other->id, 'start_date' => '2021-01-01']))->assertCreated();
        $this->assertSame($other->id, $employee->fresh()->department_id);
        $this->getJson(route('hr.person.history.index', $employee))->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_invalid_manager_branch_designation_and_transfer_are_rejected(): void
    {
        [$employee, $data] = $this->employeeData();
        $url = route('hr.person.history.store', $employee);
        $this->postJson($url, array_merge($data, ['manager_id' => $employee->id]))->assertUnprocessable()->assertJsonValidationErrors('manager_id');
        $branch = Branch::create(['branch_code' => 'OTHER', 'name' => 'Other']);
        $this->postJson($url, array_merge($data, ['branch_id' => $branch->id]))->assertUnprocessable()->assertJsonValidationErrors('department_id');
        $designation = Designation::create(['name' => 'Inactive', 'is_active' => false]);
        $this->postJson($url, array_merge($data, ['designation_id' => $designation->id]))->assertUnprocessable()->assertJsonValidationErrors('designation_id');
        $transfer = route('hr.person.history.transfer', $employee);
        $this->postJson($transfer, $data)->assertUnprocessable();
        $this->postJson($url, $data)->assertCreated();
        $this->postJson($transfer, $data)->assertUnprocessable();
        $this->postJson($transfer, array_merge($data, ['start_date' => '2021-01-01']))->assertUnprocessable();
        $this->assertDatabaseCount('employment_history', 1);
        $this->assertNull($employee->employmentHistories()->first()->end_date);
    }

    public function test_history_requires_authentication(): void
    {
        $this->getJson(route('hr.person.history.index', 1))->assertUnauthorized();
        $this->postJson(route('hr.person.history.store', 1), [])->assertUnauthorized();
        $this->postJson(route('hr.person.history.transfer', 1), [])->assertUnauthorized();
    }
}
