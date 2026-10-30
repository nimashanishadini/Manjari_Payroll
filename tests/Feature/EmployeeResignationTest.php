<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeResignationTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): Employee
    {
        $branch = Branch::create(['branch_code' => 'HQ', 'name' => 'Head Office']);
        $department = Department::create(['branch_id' => $branch->id, 'name' => 'Human Resources']);

        return Employee::create(['employee_id' => 'EMP123', 'name' => 'Test Employee', 'first_name' => 'Test', 'last_name' => 'Employee',
            'branch_id' => $branch->id, 'department_id' => $department->id, 'employment_type' => 'Permanent', 'status' => 'Active',
            'joined_date' => '2026-01-01', 'allow_self_service' => true]);
    }

    public function test_admin_can_resign_employee_and_record_stays_in_directory_and_resigned_list(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();
        $this->actingAs($admin);
        $this->postJson(route('hr.person.employees.resign', $employee), ['resigned_on' => '2026-10-01', 'resignation_reason' => 'Career development'])
            ->assertOk()->assertJsonPath('status', 'Resigned');
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'status' => 'Resigned', 'resignation_reason' => 'Career development', 'resigned_by' => $admin->id, 'deleted_at' => null, 'allow_self_service' => false]);
        $this->withoutVite()->get(route('hr.person.index'))->assertOk()->assertSee('EMP123')->assertSee('Resigned');
        $this->get(route('hr.person.resigned'))->assertOk()->assertSee('Test Employee')->assertSee('EMP123')->assertSee('Career development')->assertSee('Human Resources')->assertSee('2026-10-01');
        $this->getJson(route('hr.person.lookup', ['employee_id' => 'EMP123']))->assertOk()->assertJsonPath('employee.resigned_on', '2026-10-01');
        $this->get(route('hr.person.create', ['employee' => $employee->id]))->assertOk()->assertSee('Mark as resigned');
        $this->postJson(route('hr.person.sections.store'), ['section' => 'basic', 'record_id' => $employee->id, 'employee_id' => 'EMP123',
            'name' => 'Updated Employee', 'branch_id' => $employee->branch_id, 'department_id' => $employee->department_id,
            'joined_date' => '2026-01-01', 'employment_type' => 'Permanent', 'allow_self_service' => 1, 'status' => 'Active'])->assertOk();
        $this->assertSame('Resigned', $employee->fresh()->status);
        $this->assertFalse((bool) $employee->fresh()->allow_self_service);
        $this->postJson(route('hr.person.employees.resign', $employee), ['resigned_on' => '2026-10-02', 'resignation_reason' => 'Replacement reason'])->assertUnprocessable();
        $this->assertSame('Career development', $employee->fresh()->resignation_reason);
    }

    public function test_only_admin_can_record_resignation_and_hr_can_view_the_list(): void
    {
        $employee = $this->employee();
        $url = route('hr.person.employees.resign', $employee);
        $this->postJson($url, [])->assertUnauthorized();
        $this->get(route('hr.person.resigned'))->assertRedirect(route('login'));
        $user = User::factory()->create();
        $this->actingAs($user)->postJson($url, [])->assertForbidden();
        $this->get(route('hr.person.resigned'))->assertForbidden();
        $this->withoutVite()->get(route('hr.person.create'))->assertOk()->assertDontSee('id="openResignation"', false);
        $user->role = 'hr';
        $user->save();
        $this->postJson($url, [])->assertForbidden();
        $this->get(route('hr.person.resigned'))->assertOk();
        $this->assertSame('Active', $employee->fresh()->status);
    }

    public function test_resignation_requires_reason_and_a_valid_date_within_employment_period(): void
    {
        $employee = $this->employee();
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();
        $this->actingAs($admin);
        $url = route('hr.person.employees.resign', $employee);
        $this->postJson($url, [])->assertUnprocessable()->assertJsonValidationErrors(['resigned_on', 'resignation_reason']);
        $this->postJson($url, ['resigned_on' => '2025-12-31', 'resignation_reason' => 'Reason'])->assertUnprocessable()->assertJsonValidationErrors('resigned_on');
        $this->postJson($url, ['resigned_on' => now('Asia/Colombo')->addDay()->toDateString(), 'resignation_reason' => 'Reason'])->assertUnprocessable();
        $this->postJson($url, ['resigned_on' => '2026-10-01', 'resignation_reason' => '   '])->assertUnprocessable();
        $this->get(route('hr.person.resigned', ['search' => 'EMP123']))->assertOk()->assertDontSee('Test Employee');
    }
}
