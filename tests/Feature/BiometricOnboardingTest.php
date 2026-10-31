<?php

namespace Tests\Feature;

use App\Models\BiometricDevice;
use App\Models\BiometricStagingUser;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use App\Services\BiometricOnboardingService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BiometricOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function setupEnrollment(): array
    {
        $user = User::factory()->create();
        $user->role = 'hr';
        $user->save();
        $this->actingAs($user);
        $branch = Branch::create(['branch_code' => 'HQ', 'name' => 'Head Office']);
        $department = Department::create(['branch_id' => $branch->id, 'name' => 'HR']);
        $designation = Designation::create(['name' => 'Executive', 'is_active' => true]);
        $device = BiometricDevice::create(['name' => 'Entrance', 'brand' => 'Hikvision', 'serial_number' => 'DEV001', 'branch_id' => $branch->id,
            'department_id' => $department->id, 'ip_address' => '192.168.1.100', 'port' => 8000, 'timezone' => 'Asia/Colombo', 'is_active' => true]);
        $staging = app(BiometricOnboardingService::class)->stage($device, '00025', 'Kamal');

        return [$staging, ['employee_id' => '00025', 'name' => 'Kamal Perera', 'branch_id' => $branch->id, 'department_id' => $department->id,
            'designation_id' => $designation->id, 'manager_id' => null, 'joined_date' => '2026-01-01', 'employment_type' => 'Permanent', 'create_login' => 0]];
    }

    public function test_pending_enrollment_can_be_reviewed_and_promoted_without_login(): void
    {
        [$staging, $data] = $this->setupEnrollment();
        $this->withoutVite()->get(route('hr.person.to-be-reviewed'))->assertOk()->assertSee('00025')->assertSee('Pending HR Review')->assertSee('Review');
        $this->get(route('hr.person.create', ['staging' => $staging->id]))->assertOk()->assertSee('Review Enrollment')->assertSee('00025')->assertSee('Optional employee login');
        $result = $this->postJson(route('hr.person.enrollments.promote', $staging), $data)->assertOk();
        $employee = Employee::findOrFail($result->json('id'));
        $this->assertSame('00025', $employee->employee_id);
        $this->assertSame('Kamal Perera', $employee->name);
        $this->assertNull($employee->user_id);
        $this->assertFalse((bool) $employee->allow_self_service);
        $this->assertDatabaseHas('employment_history', ['employee_id' => $employee->id, 'designation_id' => $data['designation_id'], 'end_date' => null, 'change_reason' => 'Biometric onboarding']);
        $this->assertDatabaseHas('biometric_staging_users', ['id' => $staging->id, 'status' => 'Promoted', 'promoted_employee_id' => $employee->id, 'reviewed_by' => auth()->id()]);
        $this->get($result->json('redirect_url'))->assertOk()->assertSee('Kamal Perera');
        $this->get(route('hr.person.to-be-reviewed', ['status' => 'Promoted']))->assertOk()->assertSee('00025');
        $this->postJson(route('hr.person.enrollments.promote', $staging), $data)->assertUnprocessable();
        $this->get(route('hr.person.create', ['staging' => $staging->id]))->assertStatus(409);
        $this->assertDatabaseCount('employees', 1);
    }

    public function test_raw_name_is_optional_but_employee_name_is_required_for_promotion(): void
    {
        [$existing, $data] = $this->setupEnrollment();
        $this->post(route('hr.person.enrollments.store'), ['device_id' => $existing->device_id, 'raw_enrollment_id' => '00026'])->assertRedirect();
        $staging = BiometricStagingUser::where('raw_enrollment_id', '00026')->firstOrFail();
        $this->assertNull($staging->raw_name);
        $this->assertNotNull($staging->fetched_at);
        $this->withoutVite()->get(route('hr.person.to-be-reviewed'))->assertOk()->assertSee('Name not provided');
        $this->get(route('hr.person.create', ['staging' => $staging->id]))->assertOk()->assertSee('00026');
        $data['employee_id'] = '00026';
        $data['name'] = '';
        $this->postJson(route('hr.person.enrollments.promote', $staging), $data)->assertUnprocessable()->assertJsonValidationErrors('name');
        $data['name'] = 'Full Employee Name';
        $this->postJson(route('hr.person.enrollments.promote', $staging), $data)->assertOk();
        $this->assertDatabaseHas('employees', ['employee_id' => '00026', 'name' => 'Full Employee Name']);
    }

    public function test_fetch_time_is_distinct_and_repeated_fetches_do_not_reset_review_status(): void
    {
        [$staging] = $this->setupEnrollment();
        $service = app(BiometricOnboardingService::class);
        $first = Carbon::parse('2026-01-01 09:00:00');
        $row = $service->stage($staging->device, 'FETCH001', null, $first);
        $this->assertSame('2026-01-01 09:00:00', $row->fetched_at->format('Y-m-d H:i:s'));
        $service->ignore($row, auth()->user(), 'Test record');
        $service->stage($staging->device, 'FETCH001', null, $first->copy()->addHour());
        $this->assertSame('Ignored', $row->fresh()->status);
        $this->assertSame('2026-01-01 10:00:00', $row->fresh()->fetched_at->format('Y-m-d H:i:s'));
        $service->stage($staging->device, 'FETCH001', null, $first);
        $this->assertSame('2026-01-01 10:00:00', $row->fresh()->fetched_at->format('Y-m-d H:i:s'));
    }

    public function test_erd_alignment_migration_preserves_existing_records_keys_and_review_state(): void
    {
        [$staging] = $this->setupEnrollment();
        app(BiometricOnboardingService::class)->ignore($staging, auth()->user(), 'Preserve review');
        $migration = require database_path('migrations/2026_10_30_170000_align_biometric_staging_fields_with_erd.php');
        $migration->down();
        DB::table('biometric_staging_users')->where('id', $staging->id)->update(['created_at' => '2026-01-02 08:30:00']);
        $this->assertDatabaseHas('biometric_staging_users', ['id' => $staging->id, 'enrollment_id' => '00025', 'name' => 'Kamal']);
        $migration->up();
        $migration->up(); // Resuming an already-applied DDL step must retain records.
        $row = $staging->fresh();
        $this->assertSame('00025', $row->raw_enrollment_id);
        $this->assertSame('Kamal', $row->raw_name);
        $this->assertSame('Ignored', $row->status);
        $this->assertSame('Preserve review', $row->ignore_reason);
        $this->assertSame('2026-01-02 08:30:00', $row->fetched_at->format('Y-m-d H:i:s'));
        $this->assertSame($row->device_id, $row->device->id);
        $this->assertSame($row->id, $row->device->stagingUsers()->first()->id);
        $indexes = Schema::getIndexes('biometric_staging_users');
        $this->assertTrue(collect($indexes)->contains(fn ($index) => $index['unique'] && $index['columns'] === ['device_id', 'raw_enrollment_id']));
    }

    public function test_optional_login_is_hashed_linked_and_always_employee_role(): void
    {
        [$staging, $data] = $this->setupEnrollment();
        $data = array_merge($data, ['create_login' => 1, 'login_email' => 'kamal@example.com', 'login_password' => 'EmployeePass123!',
            'login_password_confirmation' => 'EmployeePass123!', 'role' => 'admin']);
        $this->postJson(route('hr.person.enrollments.promote', $staging), $data)->assertOk();
        $employee = Employee::firstOrFail();
        $user = User::findOrFail($employee->user_id);
        $this->assertSame('employee', $user->role);
        $this->assertTrue(Hash::check('EmployeePass123!', $user->password));
        $this->assertTrue((bool) $employee->allow_self_service);
        $this->assertSame('kamal@example.com', $user->email);
    }

    public function test_repeated_manual_entry_does_not_duplicate_or_reset_ignored_record(): void
    {
        [$staging] = $this->setupEnrollment();
        $payload = ['device_id' => $staging->device_id, 'raw_enrollment_id' => '00025', 'raw_name' => 'Changed keypad name'];
        $this->post(route('hr.person.enrollments.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('biometric_staging_users', 1);
        $this->assertSame('Kamal', $staging->fresh()->raw_name);
        $this->post(route('hr.person.enrollments.ignore', $staging), ['ignore_reason' => 'Test enrollment'])->assertRedirect();
        $this->post(route('hr.person.enrollments.store'), $payload)->assertRedirect();
        $this->assertSame('Ignored', $staging->fresh()->status);
        $this->assertSame('Test enrollment', $staging->fresh()->ignore_reason);
        $this->postJson(route('hr.person.enrollments.ignore', $staging), ['ignore_reason' => 'Changed reason'])->assertUnprocessable();
        $this->assertDatabaseCount('employees', 0);
        $payload['raw_enrollment_id'] = '00026';
        $this->post(route('hr.person.enrollments.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('biometric_staging_users', 2);
    }

    public function test_ids_branch_department_and_login_validation_protect_promotion(): void
    {
        [$staging, $data] = $this->setupEnrollment();
        $url = route('hr.person.enrollments.promote', $staging);
        $other = Branch::create(['branch_code' => 'OTHER', 'name' => 'Other']);
        $department = Department::create(['branch_id' => $other->id, 'name' => 'Other department']);
        $this->postJson($url, array_merge($data, ['employee_id' => 'OTHER', 'branch_id' => $other->id, 'department_id' => $department->id]))
            ->assertUnprocessable()->assertJsonValidationErrors(['employee_id', 'branch_id', 'department_id']);
        $this->postJson($url, array_merge($data, ['create_login' => 1]))->assertUnprocessable()->assertJsonValidationErrors(['login_email', 'login_password']);
        $this->postJson($url, array_merge($data, ['record_id' => 1]))->assertUnprocessable();
        $this->postJson(route('hr.person.enrollments.ignore', $staging), [])->assertUnprocessable()->assertJsonValidationErrors('ignore_reason');
        $this->assertSame('Pending HR Review', $staging->fresh()->status);
        $this->assertDatabaseCount('employees', 0);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_duplicate_employee_enrollment_is_blocked_across_devices(): void
    {
        [$staging, $data] = $this->setupEnrollment();
        $otherDevice = $staging->device->replicate();
        $otherDevice->serial_number = 'DEV002';
        $otherDevice->save();
        $other = app(BiometricOnboardingService::class)->stage($otherDevice, '00025', 'Kamal');
        $this->postJson(route('hr.person.enrollments.promote', $staging), $data)->assertOk();
        $this->postJson(route('hr.person.enrollments.promote', $other), $data)->assertUnprocessable()->assertJsonValidationErrors('employee_id');
        $this->assertSame('Pending HR Review', $other->fresh()->status);
        $this->assertDatabaseCount('employees', 1);
    }

    public function test_transaction_rolls_back_new_login_when_employee_creation_fails(): void
    {
        [$staging, $data] = $this->setupEnrollment();
        Employee::create(['employee_id' => 'OTHER', 'first_name' => 'Existing', 'last_name' => 'Employee', 'email' => 'work@example.com',
            'branch_id' => $data['branch_id'], 'department_id' => $data['department_id'], 'employment_type' => 'Permanent']);
        $data = array_merge($data, ['email' => 'work@example.com', 'create_login' => 1, 'login_email' => 'newlogin@example.com', 'login_password' => 'EmployeePass123!']);
        try {
            app(BiometricOnboardingService::class)->promote($staging, $data, auth()->user());
            $this->fail('Expected a unique constraint failure after login creation.');
        } catch (UniqueConstraintViolationException $error) {
            $this->assertDatabaseMissing('users', ['email' => 'newlogin@example.com']);
            $this->assertSame('Pending HR Review', $staging->fresh()->status);
            $this->assertDatabaseCount('employment_history', 0);
            $this->assertDatabaseCount('employees', 1);
        }
    }

    public function test_employee_users_cannot_access_review_or_promote(): void
    {
        $this->get(route('hr.person.to-be-reviewed'))->assertRedirect(route('login'));
        [$staging, $data] = $this->setupEnrollment();
        $this->actingAs(User::factory()->create());
        $this->get(route('hr.person.to-be-reviewed'))->assertForbidden();
        $this->get(route('hr.person.create', ['staging' => $staging->id]))->assertForbidden();
        $this->postJson(route('hr.person.enrollments.store'), [])->assertForbidden();
        $this->postJson(route('hr.person.enrollments.promote', $staging), $data)->assertForbidden();
        $this->postJson(route('hr.person.enrollments.ignore', $staging), ['ignore_reason' => 'Test'])->assertForbidden();
    }

    public function test_disabled_devices_reject_new_enrollments_and_promotion(): void
    {
        [$staging, $data] = $this->setupEnrollment();
        $staging->device->update(['is_active' => false]);
        $this->postJson(route('hr.person.enrollments.store'), ['device_id' => $staging->device_id, 'raw_enrollment_id' => 'NEW', 'raw_name' => 'Test'])->assertUnprocessable();
        $this->postJson(route('hr.person.enrollments.promote', $staging), $data)->assertUnprocessable();
        $this->assertDatabaseCount('employees', 0);
    }
}
