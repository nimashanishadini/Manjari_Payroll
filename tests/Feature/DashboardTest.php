<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_the_dashboard(): void
    {
        $this->get('/')
            ->assertRedirect('/dashboard');
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');

        $this->get('/login')
            ->assertOk()
            ->assertSee('Log in to your account');
    }

    public function test_authenticated_users_can_view_the_dashboard_and_sidebar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Sales')
            ->assertSee('Inventory')
            ->assertSee('Organization Structure')
            ->assertSee('Employee Management')
            ->assertSee('Shift &amp; Attendance', false)
            ->assertSee('Leaves &amp; Approvals', false)
            ->assertSee('Payroll &amp; Payslips', false)
            ->assertSee('HR Reports')
            ->assertSee('Welcome back, '.$user->name)
            ->assertSee('Logout');
    }

    public function test_hr_subsections_have_separate_authenticated_pages(): void
    {
        $user = User::factory()->create();

        $pages = [
            '/hr/organization-structure' => 'Organization Structure',
            '/hr/employee-management' => 'Employee Management',
            '/hr/shifts-attendance' => 'Shift &amp; Attendance',
            '/hr/leaves' => 'Leaves &amp; Approvals',
            '/hr/payroll' => 'Payroll &amp; Payslips',
            '/hr/reports' => 'HR Reports',
        ];

        foreach ($pages as $path => $heading) {
            $this->actingAs($user)
                ->get($path)
                ->assertOk()
                ->assertSee($heading, false)
                ->assertSee('HR &amp; Payroll', false)
                ->assertSee('Partner IT ERP Enterprise System')
                ->assertSee('Server Time:')
                ->assertSee('footer-server-time');
        }
    }

    public function test_organization_and_employee_pages_show_existing_records(): void
    {
        $user = User::factory()->create();
        $branch = Branch::create([
            'branch_code' => 'HQ',
            'name' => 'Head Office',
            'timezone' => 'UTC',
        ]);
        $department = Department::create([
            'branch_id' => $branch->id,
            'name' => 'Human Resources',
        ]);
        Employee::create([
            'employee_id' => 'EMP-001',
            'first_name' => 'Nima',
            'last_name' => 'Perera',
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'employment_type' => 'Permanent',
        ]);

        $this->actingAs($user)
            ->get('/hr/organization-structure')
            ->assertOk()
            ->assertSee('Head Office')
            ->assertSee('Human Resources');

        $this->actingAs($user)
            ->get('/hr/employee-management')
            ->assertOk()
            ->assertSee('EMP-001')
            ->assertSee('Nima Perera')
            ->assertSee('Human Resources');
    }

    public function test_browser_login_and_logout_redirect_while_json_auth_keeps_api_responses(): void
    {
        $user = User::factory()->create();

        $this->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                '_redirect_after_login' => '1',
            ])
            ->assertRedirect('/dashboard');

        $this->post('/logout', [
            '_redirect_after_logout' => '1',
        ])
            ->assertRedirect('/login');

        $this->actingAs($user)
            ->postJson('/logout')
            ->assertNoContent();
    }
}
