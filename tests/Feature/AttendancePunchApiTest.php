<?php

namespace Tests\Feature;

use App\Models\BiometricDevice;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\RawAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendancePunchApiTest extends TestCase
{
    use RefreshDatabase;

    private function setupDevice(): array
    {
        $branch = Branch::create(['branch_code' => 'HQ', 'name' => 'HQ']);
        $department = Department::create(['branch_id' => $branch->id, 'name' => 'IT']);
        $device = BiometricDevice::create(['name' => 'Entrance', 'brand' => 'Hikvision', 'serial_number' => 'D1',
            'branch_id' => $branch->id, 'ip_address' => '192.168.1.2', 'port' => 80, 'timezone' => 'Asia/Colombo', 'is_active' => true]);
        $device->forceFill(['bridge_token_hash' => hash('sha256', str_repeat('a', 64)), 'bridge_token_expires_at' => now()->addDay()])->save();
        $employee = Employee::create(['employee_id' => '00025', 'first_name' => 'Test', 'last_name' => 'Employee', 'name' => 'Test',
            'branch_id' => $branch->id, 'department_id' => $department->id, 'employment_type' => 'Permanent']);
        return [$device, $employee];
    }

    private function payload(): array
    {
        return ['employee_id' => '00025', 'punch_time' => '2026-01-01T08:30:00.123456+05:30', 'verify_type' => 'Fingerprint'];
    }

    private function url($device): string
    {
        return route('api.biometric.punches.store', $device->id);
    }

    public function test_punch_uses_natural_employee_key_device_branch_and_utc_with_idempotent_retry(): void
    {
        [$device, $employee] = $this->setupDevice();
        $this->withToken(str_repeat('a', 64))->postJson($this->url($device), $this->payload())
            ->assertCreated()->assertJsonPath('data.punch_time', '2026-01-01T03:00:00.123456Z')->assertJsonPath('data.created', true);
        $row = RawAttendance::firstOrFail();
        $this->assertSame($employee->id, $row->employee->id);
        $this->assertSame('HQ', $row->branch_code);
        $this->assertSame('Device', $row->source);
        $this->assertNull($row->punch_state);
        $this->assertNull($row->state_source);
        $this->assertFalse($row->is_anomalous);
        $this->postJson($this->url($device), array_replace($this->payload(), ['punch_time' => '2026-01-01T03:00:00.123456Z', 'verify_type' => 'Changed']))
            ->assertOk()->assertJsonPath('data.created', false)->assertJsonPath('data.id', $row->id);
        $this->assertSame('Fingerprint', $row->fresh()->verify_type);
        $this->assertDatabaseCount('raw_attendance', 1);
        $this->assertNull($device->fresh()->last_sync_at);
    }

    public function test_unknown_employee_can_be_retried_after_registration(): void
    {
        [$device, $employee] = $this->setupDevice();
        $employee->forceFill(['employee_id' => 'OTHER'])->save();
        $this->withToken(str_repeat('a', 64))->postJson($this->url($device), $this->payload())
            ->assertStatus(409)->assertJsonPath('code', 'employee_not_registered')->assertJsonPath('retryable', true)->assertJsonPath('stored', false);
        $this->assertDatabaseCount('raw_attendance', 0);
        $employee->forceFill(['employee_id' => '00025'])->save();
        $this->postJson($this->url($device), $this->payload())->assertCreated();
    }

    public function test_credentials_device_scope_and_disabled_device(): void
    {
        [$device] = $this->setupDevice();
        $this->postJson($this->url($device), $this->payload())->assertUnauthorized();
        $this->withToken(str_repeat('b', 64))->postJson($this->url($device), $this->payload())->assertUnauthorized();
        $device->forceFill(['bridge_token_expires_at' => now()->subSecond()])->save();
        $this->withToken(str_repeat('a', 64))->postJson($this->url($device), $this->payload())->assertUnauthorized();
        $device->forceFill(['bridge_token_expires_at' => now()->addDay(), 'is_active' => false])->save();
        $this->postJson($this->url($device), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('raw_attendance', 0);
    }

    public function test_rejects_invalid_or_spoofed_punch_metadata(): void
    {
        [$device] = $this->setupDevice();
        $this->withToken(str_repeat('a', 64));
        foreach ([['employee_id' => 25], ['employee_id' => 'bad id'], ['punch_time' => '2026-01-01 08:00:00'],
            ['punch_time' => now()->addDay()->toIso8601String()], ['punch_state' => 'Check-In'], ['source' => 'Correction'],
            ['branch_code' => 'OTHER'], ['device_id' => 99], ['is_anomalous' => true]] as $invalid) {
            $this->postJson($this->url($device), array_replace($this->payload(), $invalid))->assertUnprocessable();
        }
        $this->assertDatabaseCount('raw_attendance', 0);
    }

    public function test_past_punches_for_resigned_and_soft_deleted_employees_and_natural_fk_protection(): void
    {
        [$device, $employee] = $this->setupDevice();
        $employee->forceFill(['status' => 'Resigned'])->save();
        $employee->delete();
        $this->withToken(str_repeat('a', 64))->postJson($this->url($device), $this->payload())->assertCreated();
        $this->assertSame($employee->id, RawAttendance::first()->employee->id);
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('employees')->where('id', $employee->id)->update(['employee_id' => 'CHANGED']);
    }
}
