<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SystemAlarm;
use App\Models\User;
use App\Services\EmployeeDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlarmNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'hr'): User
    {
        $user = User::factory()->create();
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function alarm(): SystemAlarm
    {
        $this->travelTo(Carbon::parse('2026-10-30 12:00:00', 'Asia/Colombo'));
        Storage::fake('employee_documents');
        $branch = Branch::create(['branch_code' => 'HQ', 'name' => 'Head Office']);
        $department = Department::create(['branch_id' => $branch->id, 'name' => 'HR']);
        $employee = Employee::create(['employee_id' => 'EMP100', 'first_name' => 'Test', 'last_name' => 'Employee', 'name' => 'Test Employee',
            'branch_id' => $branch->id, 'department_id' => $department->id, 'employment_type' => 'Permanent', 'status' => 'Active']);
        Storage::disk('employee_documents')->put('test.pdf', 'test document');
        $employee->documents = ['driving_license' => ['files' => [['id' => 'test-file', 'disk' => 'employee_documents', 'path' => 'test.pdf', 'name' => 'licence.pdf', 'expires_on' => '2026-10-31']]]];
        $employee->save();
        app(EmployeeDocumentService::class)->sync($employee);

        return SystemAlarm::firstOrFail();
    }

    public function test_header_notifications_open_exact_employee_document_and_reads_are_per_user(): void
    {
        $alarm = $this->alarm();
        $first = $this->user();
        $second = $this->user('admin');
        $this->actingAs($first)->withoutVite()->get(route('dashboard'))->assertOk()->assertSee('id="alarmNotifications"', false)
            ->assertDontSee('href="'.route('hr.person.system-alarms.index').'"', false);
        $feed = route('hr.person.notifications.index');
        $this->getJson($feed)->assertOk()->assertJsonPath('unread_count', 1)->assertJsonPath('notifications.0.is_read', false)->assertJsonPath('notifications.0.employee_id', 'EMP100');
        $response = $this->post(route('hr.person.notifications.open', $alarm))->assertRedirect();
        $destination = route('hr.person.create', ['employee' => $alarm->document->employee_id, 'tab' => 'documents', 'document_type' => 'driving_license', 'document_file' => 'test-file']);
        $response->assertRedirect($destination);
        $this->get($destination)->assertOk()->assertSee('licence.pdf');
        $this->getJson($feed)->assertOk()->assertJsonPath('unread_count', 0)->assertJsonPath('notifications.0.is_read', true);
        $this->post(route('hr.person.notifications.open', $alarm))->assertRedirect($destination);
        $this->assertDatabaseCount('system_alarm_reads', 1);
        $this->actingAs($second)->getJson($feed)->assertOk()->assertJsonPath('unread_count', 1)->assertJsonPath('notifications.0.is_read', false);
    }

    public function test_expired_status_becomes_unread_again_and_renewal_removes_notification(): void
    {
        $alarm = $this->alarm();
        $this->actingAs($this->user());
        $this->post(route('hr.person.notifications.open', $alarm))->assertRedirect();
        $this->travelTo(Carbon::parse('2026-11-01 12:00:00', 'Asia/Colombo'));
        $this->getJson(route('hr.person.notifications.index'))->assertOk()->assertJsonPath('unread_count', 1)->assertJsonPath('notifications.0.status', 'expired');
        $employee = $alarm->document->employee;
        $documents = $employee->documents;
        $documents['driving_license']['files'][0]['expires_on'] = '2027-12-31';
        $employee->documents = $documents;
        $employee->save();
        app(EmployeeDocumentService::class)->sync($employee);
        $this->getJson(route('hr.person.notifications.index'))->assertOk()->assertJsonPath('total', 0)->assertJsonPath('unread_count', 0)->assertJsonCount(0, 'notifications');
        $this->post(route('hr.person.notifications.open', $alarm))->assertNotFound();
    }

    public function test_notification_endpoints_are_hr_admin_only_and_deleted_employees_are_excluded(): void
    {
        $alarm = $this->alarm();
        $this->getJson(route('hr.person.notifications.index'))->assertUnauthorized();
        $this->postJson(route('hr.person.notifications.open', $alarm))->assertUnauthorized();
        $this->actingAs($this->user('employee'));
        $this->getJson(route('hr.person.notifications.index'))->assertForbidden();
        $this->postJson(route('hr.person.notifications.open', $alarm))->assertForbidden();
        $this->withoutVite()->get(route('dashboard'))->assertOk()->assertDontSee('id="alarmNotifications"', false);
        $alarm->document->employee->delete();
        $this->actingAs($this->user())->getJson(route('hr.person.notifications.index'))->assertOk()->assertJsonPath('total', 0);
        $this->postJson(route('hr.person.notifications.open', $alarm))->assertNotFound();
    }
}
