<?php

namespace Tests\Feature;

use App\Models\BiometricDevice;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SystemAlarm;
use App\Models\User;
use App\Services\BiometricOnboardingService;
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

    public function test_pending_enrollments_share_the_bell_and_open_review_with_per_user_read_state(): void
    {
        $alarm = $this->alarm();
        $employee = $alarm->document->employee;
        $device = BiometricDevice::create(['name' => 'Entrance', 'brand' => 'Hikvision', 'serial_number' => 'DEVICE001',
            'branch_id' => $employee->branch_id, 'ip_address' => '192.168.1.100', 'port' => 8000, 'timezone' => 'Asia/Colombo', 'is_active' => true]);
        $staging = app(BiometricOnboardingService::class)->stage($device, '00025', 'Kamal');
        $reviewer = $this->user();
        $this->actingAs($reviewer);
        $feed = route('hr.person.notifications.index');
        $this->getJson($feed)->assertOk()->assertJsonPath('total', 2)->assertJsonPath('unread_count', 2)
            ->assertJsonFragment(['type' => 'enrollment', 'enrollment_id' => '00025']);
        $url = route('hr.person.notifications.enrollment.open', $staging);
        $this->post($url)->assertRedirect(route('hr.person.create', ['staging' => $staging->id]));
        $this->getJson($feed)->assertOk()->assertJsonPath('unread_count', 1)->assertJsonFragment(['type' => 'enrollment', 'is_read' => true]);
        $this->post($url)->assertRedirect();
        $this->assertDatabaseCount('biometric_staging_reads', 1);
        $this->actingAs($this->user('admin'))->getJson($feed)->assertOk()->assertJsonPath('unread_count', 2);
        $this->post(route('hr.person.enrollments.ignore', $staging), ['ignore_reason' => 'Test enrollment'])->assertRedirect();
        $this->getJson($feed)->assertOk()->assertJsonPath('total', 1)->assertJsonMissing(['enrollment_id' => '00025']);
        $this->post($url)->assertRedirect(route('hr.person.to-be-reviewed', ['status' => 'Ignored', 'search' => '00025']));
        $this->actingAs($this->user('employee'))->postJson($url)->assertForbidden();
    }

    public function test_mixed_notifications_paginate_without_omitting_pending_enrollments(): void
    {
        $alarm = $this->alarm();
        $device = BiometricDevice::create(['name' => 'Entrance', 'brand' => 'Hikvision', 'serial_number' => 'DEVICE001',
            'branch_id' => $alarm->document->employee->branch_id, 'ip_address' => '192.168.1.100', 'port' => 8000, 'timezone' => 'Asia/Colombo', 'is_active' => true]);
        for ($i = 1; $i <= 12; $i++) {
            app(BiometricOnboardingService::class)->stage($device, 'NEW'.$i, null);
        }
        $this->actingAs($this->user());
        $first = $this->getJson(route('hr.person.notifications.index'))->assertOk()->assertJsonPath('total', 13)->assertJsonPath('unread_count', 13)->assertJsonCount(10, 'notifications');
        $second = $this->getJson($first->json('next_page_url'))->assertOk()->assertJsonCount(3, 'notifications')->assertJsonPath('next_page_url', null);
        $keys = collect([...$first->json('notifications'), ...$second->json('notifications')])->map(fn ($item) => $item['type'].':'.$item['id']);
        $this->assertCount(13, $keys->unique());
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
        $this->get(route('dashboard'))->assertRedirect(route('self-service.index'));
        $this->withoutVite()->get(route('self-service.index'))->assertOk()->assertDontSee('id="alarmNotifications"', false);
        $alarm->document->employee->delete();
        $this->actingAs($this->user())->getJson(route('hr.person.notifications.index'))->assertOk()->assertJsonPath('total', 0);
        $this->postJson(route('hr.person.notifications.open', $alarm))->assertNotFound();
    }
}
