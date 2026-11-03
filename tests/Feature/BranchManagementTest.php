<?php

namespace Tests\Feature;

use App\Models\BiometricDevice;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchManagementTest extends TestCase
{
    use RefreshDatabase;

    private function hr(string $role = 'hr'): void
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();
        $this->actingAs($user);
    }

    public function test_hr_creates_searches_and_edits_branch_and_directory_receives_new_branch(): void
    {
        $this->hr();
        $this->post(route('hr.branches.store'), ['branch_code' => 'CMB_HQ', 'name' => '  Colombo Office  ', 'timezone' => 'Asia/Colombo'])
            ->assertRedirect(route('hr.organization-structure.index'));
        $branch = Branch::where('branch_code', 'CMB_HQ')->firstOrFail();
        $this->assertSame('Colombo Office', $branch->name);
        Branch::create(['branch_code' => 'GAL', 'name' => 'Galle Office', 'timezone' => 'Asia/Colombo']);
        $this->withoutVite()->get(route('hr.organization-structure.index', ['search' => 'CMB_HQ']))
            ->assertOk()->assertSee('Colombo Office')->assertSee('Add branch')->assertViewHas('branches', fn ($rows) => $rows->count() === 1);
        $this->put(route('hr.branches.update', $branch), ['name' => 'Colombo Head Office', 'timezone' => 'UTC'])->assertRedirect();
        $this->assertSame('UTC', $branch->fresh()->timezone);
        $this->assertSame('CMB_HQ', $branch->fresh()->branch_code);
        $this->get(route('hr.person.index'))->assertOk()->assertSee('Colombo Head Office');
    }

    public function test_invalid_data_duplicates_and_code_reassignment_are_rejected(): void
    {
        $this->hr();
        $branch = Branch::create(['branch_code' => 'HQ', 'name' => 'HQ']);
        foreach ([['branch_code' => 'HQ'], ['branch_code' => 'bad code'], ['name' => ' '], ['timezone' => 'Not/AZone']] as $invalid) {
            $this->postJson(route('hr.branches.store'), array_replace(['branch_code' => 'NEW', 'name' => 'New Branch', 'timezone' => 'Asia/Colombo'], $invalid))->assertUnprocessable();
        }
        $this->putJson(route('hr.branches.update', $branch), ['branch_code' => 'CHANGED', 'name' => 'Updated', 'timezone' => 'UTC'])
            ->assertUnprocessable()->assertJsonValidationErrors('branch_code');
        $this->assertDatabaseCount('branches', 1);
        $this->assertSame('HQ', $branch->fresh()->name);
    }

    public function test_unused_branch_can_be_deleted_but_departments_and_devices_block_deletion(): void
    {
        $this->hr('admin');
        $empty = Branch::create(['branch_code' => 'EMPTY', 'name' => 'Empty']);
        $this->delete(route('hr.branches.destroy', $empty))->assertRedirect();
        $this->assertDatabaseMissing('branches', ['id' => $empty->id]);
        $branch = Branch::create(['branch_code' => 'DEPT', 'name' => 'Departments']);
        Department::create(['branch_id' => $branch->id, 'name' => 'IT']);
        $this->deleteJson(route('hr.branches.destroy', $branch))->assertUnprocessable();
        $deviceBranch = Branch::create(['branch_code' => 'DEV', 'name' => 'Device Branch']);
        BiometricDevice::create(['name' => 'Terminal', 'branch_id' => $deviceBranch->id, 'brand' => 'Hikvision',
            'serial_number' => 'TEST01', 'ip_address' => '192.168.1.2', 'port' => 80, 'timezone' => 'Asia/Colombo', 'is_active' => true]);
        $this->deleteJson(route('hr.branches.destroy', $deviceBranch))->assertUnprocessable();
        $this->assertDatabaseHas('branches', ['id' => $deviceBranch->id]);
    }

    public function test_soft_deleted_employee_branch_snapshot_is_retained(): void
    {
        $this->hr();
        $historical = Branch::create(['branch_code' => 'OLD', 'name' => 'Old Branch']);
        $current = Branch::create(['branch_code' => 'NEW', 'name' => 'Current Branch']);
        $dept = Department::create(['branch_id' => $current->id, 'name' => 'IT']);
        $employee = Employee::create(['employee_id' => 'EMP1', 'name' => 'Test', 'first_name' => 'Test', 'last_name' => 'User',
            'branch_id' => $current->id, 'branch_code' => 'OLD', 'department_id' => $dept->id, 'employment_type' => 'Permanent']);
        $employee->delete();
        $this->deleteJson(route('hr.branches.destroy', $historical))->assertUnprocessable();
        $this->assertDatabaseHas('branches', ['id' => $historical->id]);
    }

    public function test_only_hr_and_admin_can_access_branch_management(): void
    {
        $this->get(route('hr.organization-structure.index'))->assertRedirect();
        $branch = Branch::create(['branch_code' => 'HQ', 'name' => 'HQ']);
        foreach (['employee', 'manager'] as $role) {
            $this->hr($role);
            $this->get(route('hr.organization-structure.index'))->assertForbidden();
            $this->postJson(route('hr.branches.store'), [])->assertForbidden();
            $this->putJson(route('hr.branches.update', $branch), [])->assertForbidden();
            $this->deleteJson(route('hr.branches.destroy', $branch))->assertForbidden();
        }
    }
}
