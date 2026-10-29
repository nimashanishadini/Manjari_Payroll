<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrApiRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_endpoint_keeps_its_api_url_and_returns_json(): void
    {
        $this->getJson('/api/employees')
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_branch_endpoint_is_mapped_to_the_hr_controller(): void
    {
        $route = app('router')->getRoutes()->match(
            \Illuminate\Http\Request::create('/api/branches', 'GET')
        );

        $this->assertSame(
            \App\Http\Controllers\Hr\BranchController::class.'@index',
            $route->getActionName()
        );

        $this->getJson('/api/branches')
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_branch_api_mutations_are_not_blocked_by_web_csrf_middleware(): void
    {
        $this->postJson('/api/branches', [
            'branch_code' => 'HQ',
            'name' => 'Head Office',
            'timezone' => 'UTC',
        ])
            ->assertCreated()
            ->assertJsonPath('data.branch_code', 'HQ');
    }
}
