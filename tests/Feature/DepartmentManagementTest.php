<?php

namespace Tests\Feature;

use App\Models\{Branch, Department, Designation, Employee, BiometricDevice, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function setupHr(): Branch
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'hr'])->save();
        $this->actingAs($user);
        return Branch::create(['branch_code' => 'HQ', 'name' => 'Head Office']);
    }

    public function test_create_edit_filter_and_delete_unused_department(): void
    {
        $branch = $this->setupHr();
        $this->post(route('hr.departments.store'), ['branch_id' => $branch->id, 'name' => '  Finance  '])->assertRedirect(route('hr.person.index'));
        $department = Department::where('name', 'Finance')->firstOrFail();
        $this->put(route('hr.departments.update', $department), ['name' => 'Financial Services'])->assertRedirect();
        $this->withoutVite()->get(route('hr.person.index', ['branch_id' => $branch->id]))
            ->assertOk()->assertSee('Financial Services')->assertSee('editDepartmentModal');
        $this->delete(route('hr.departments.destroy', $department))->assertRedirect();
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_names_unique_per_branch_and_reparenting_is_rejected(): void
    {
        $branch = $this->setupHr();
        $other = Branch::create(['branch_code' => 'OTHER', 'name' => 'Other']);
        $department = Department::create(['branch_id' => $branch->id, 'name' => 'IT']);
        $this->postJson(route('hr.departments.store'), ['branch_id' => $branch->id, 'name' => 'IT'])->assertUnprocessable();
        $this->post(route('hr.departments.store'), ['branch_id' => $other->id, 'name' => 'IT'])->assertRedirect();
        $this->putJson(route('hr.departments.update', $department), ['branch_id' => $other->id, 'name' => 'New'])->assertUnprocessable();
        Department::create(['branch_id' => $branch->id, 'name' => 'HR']);
        $this->putJson(route('hr.departments.update', $department), ['name' => 'HR'])->assertUnprocessable();
        $this->putJson(route('hr.departments.update', $department), ['name' => ' '])->assertUnprocessable();
        $this->assertSame('IT', $department->fresh()->name);
    }

    public function test_device_and_retained_employee_history_prevent_deletion(): void
    {
        $branch = $this->setupHr();
        $dept = Department::create(['branch_id' => $branch->id, 'name' => 'IT']);
        $old = Department::create(['branch_id' => $branch->id, 'name' => 'Old IT']);
        $employee = Employee::create(['employee_id' => 'EMP1', 'name' => 'Test', 'first_name' => 'Test', 'last_name' => 'User',
            'branch_id' => $branch->id, 'department_id' => $dept->id, 'employment_type' => 'Permanent']);
        $designation = Designation::create(['name' => 'Officer', 'is_active' => true]);
        $employee->employmentHistories()->create(['department_id' => $old->id, 'designation_id' => $designation->id,
            'start_date' => '2020-01-01', 'end_date' => '2021-01-01', 'change_reason' => 'Past appointment']);
        $employee->delete();
        $this->deleteJson(route('hr.departments.destroy', $dept))->assertUnprocessable();
        $this->deleteJson(route('hr.departments.destroy', $old))->assertUnprocessable();
        $deviceDept = Department::create(['branch_id' => $branch->id, 'name' => 'Security']);
        BiometricDevice::create(['name' => 'Entrance', 'brand' => 'Hikvision', 'serial_number' => 'D1', 'branch_id' => $branch->id,
            'department_id' => $deviceDept->id, 'ip_address' => '192.168.1.2', 'port' => 80, 'timezone' => 'Asia/Colombo', 'is_active' => true]);
        $this->deleteJson(route('hr.departments.destroy', $deviceDept))->assertUnprocessable();
        $this->assertDatabaseCount('departments', 3);
    }

    public function test_guest_employee_and_manager_cannot_manage_departments(): void
    {
        $this->get(route('hr.departments.index'))->assertRedirect();
        $branch = $this->setupHr();
        $department = Department::create(['branch_id' => $branch->id, 'name' => 'IT']);
        foreach (['employee', 'manager'] as $role) {
            $user = User::factory()->create();
            $user->forceFill(['role' => $role])->save();
            $this->actingAs($user);
            $this->get(route('hr.departments.index'))->assertForbidden();
            $this->postJson(route('hr.departments.store'), [])->assertForbidden();
            $this->postJson(route('hr.person.departments.store'), ['branch_id' => $branch->id, 'name' => 'Unauthorized'])->assertForbidden();
            $this->putJson(route('hr.departments.update', $department), [])->assertForbidden();
            $this->deleteJson(route('hr.departments.destroy', $department))->assertForbidden();
        }
    }
}
