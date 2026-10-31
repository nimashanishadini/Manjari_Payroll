<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    private function employeeUser(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'employee'])->save();
        return $user;
    }

    public function test_employee_login_ignores_intended_hr_page(): void
    {
        $user = $this->employeeUser();
        $this->withSession(['url.intended' => '/hr/person/resigned'])->post('/login', [
            'email' => $user->email, 'password' => 'password', '_redirect_after_login' => 1,
        ])->assertRedirect(route('self-service.index'));
        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertRedirect(route('self-service.index'));
    }

    public function test_employee_only_sees_own_profile_and_menus(): void
    {
        $user = $this->employeeUser();
        $branch = \App\Models\Branch::create(['branch_code' => 'HQ', 'name' => 'Head Office']);
        $department = \App\Models\Department::create(['branch_id' => $branch->id, 'name' => 'HR']);
        $defaults = ['first_name' => 'Test', 'last_name' => 'Employee', 'branch_id' => $branch->id, 'department_id' => $department->id, 'employment_type' => 'Permanent'];
        Employee::create($defaults + ['employee_id' => 'OWN001', 'name' => 'Own Employee', 'user_id' => $user->id]);
        $other = Employee::create($defaults + ['employee_id' => 'OTHER002', 'name' => 'Other Employee']);
        $this->actingAs($user)->withoutVite()->get('/self-service?employee='.$other->id)
            ->assertOk()->assertSee('Own Employee')->assertSee('OWN001')->assertSee('Account Settings')
            ->assertDontSee('Other Employee')->assertDontSee('To Be Reviewed')->assertDontSee('Position Management')
            ->assertDontSee('href="'.route('hr.person.resigned').'"', false)
            ->assertDontSee('href="'.route('hr.device.index').'"', false);
    }

    public function test_employee_cannot_access_management_endpoints_directly(): void
    {
        $this->actingAs($this->employeeUser());
        foreach (['/hr/person', '/person', '/hr/person/add-employee', '/hr/person/resigned', '/hr/person/to-be-reviewed', '/hr/device'] as $path) {
            $this->get($path)->assertForbidden();
        }
        $this->post('/person/employees', [])->assertForbidden();
    }

    public function test_unlinked_account_has_helpful_empty_state_and_guests_must_login(): void
    {
        $this->get('/self-service')->assertRedirect();
        $this->actingAs($this->employeeUser())->withoutVite()->get('/self-service')
            ->assertOk()->assertSee('Your employee profile has not been linked');
    }
}
