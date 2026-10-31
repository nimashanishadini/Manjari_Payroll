<?php

namespace Tests\Feature;

use App\Models\BiometricDevice;
use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiometricDeviceTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        $user = User::factory()->create();
        $user->role = 'hr';
        $user->save();
        $this->actingAs($user);
        $branch = Branch::create(['branch_code' => 'HQ', 'name' => 'Head Office', 'timezone' => 'Asia/Colombo']);
        $department = Department::create(['branch_id' => $branch->id, 'name' => 'HR']);

        return ['name' => 'Main Entrance', 'brand' => 'Hikvision', 'serial_number' => 'DEVICE001', 'branch_id' => $branch->id,
            'department_id' => $department->id, 'ip_address' => '192.168.1.100', 'port' => 8000, 'timezone' => 'Asia/Colombo', 'is_active' => 1];
    }

    public function test_devices_can_be_registered_viewed_and_edited_without_faking_sync_status(): void
    {
        $data = $this->payload();
        $this->post(route('hr.device.store'), $data + ['status' => 'Online', 'last_sync_at' => now()->toDateTimeString()])->assertRedirect(route('hr.device.index'));
        $device = BiometricDevice::firstOrFail();
        $this->assertSame('Not Synced', $device->status);
        $this->assertNull($device->last_sync_at);
        $this->assertSame('Head Office', $device->branch->name);
        $this->assertSame('HR', $device->department->name);
        $this->withoutVite()->get(route('hr.device.index'))->assertOk()->assertSee('Main Entrance')->assertSee('DEVICE001')->assertSee('Never synced');
        $this->get(route('hr.device.edit', $device))->assertOk()->assertSee('Save device changes')->assertSee('192.168.1.100');
        $data['name'] = 'Reception';
        $data['department_id'] = null;
        $data['is_active'] = 0;
        $this->put(route('hr.device.update', $device), $data)->assertRedirect(route('hr.device.index'));
        $this->assertFalse($device->fresh()->is_active);
        $this->assertNull($device->fresh()->department_id);
        $this->assertSame('Reception', $device->fresh()->name);
        $this->get(route('hr.device.index', ['active' => '0', 'search' => 'DEVICE001']))->assertOk()
            ->assertViewHas('devices', fn ($devices) => $devices->count() === 1);
        $this->get(route('hr.device.index', ['active' => '1']))->assertOk()->assertViewHas('devices', fn ($devices) => $devices->isEmpty());
    }

    public function test_device_validation_rejects_duplicates_wrong_department_and_invalid_network_settings(): void
    {
        $data = $this->payload();
        $this->post(route('hr.device.store'), $data)->assertRedirect();
        $this->postJson(route('hr.device.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('serial_number');
        $other = Branch::create(['branch_code' => 'OTHER', 'name' => 'Other']);
        $data['serial_number'] = 'DEVICE002';
        $data['branch_id'] = $other->id;
        $data['ip_address'] = 'invalid-ip';
        $data['port'] = 65536;
        $data['timezone'] = 'invalid-timezone';
        $this->postJson(route('hr.device.store'), $data)->assertUnprocessable()->assertJsonValidationErrors(['department_id', 'ip_address', 'port', 'timezone']);
        $this->assertDatabaseCount('biometric_devices', 1);
    }

    public function test_only_hr_and_admin_can_access_device_management(): void
    {
        $this->get(route('hr.device.index'))->assertRedirect(route('login'));
        $this->postJson(route('hr.device.store'), [])->assertUnauthorized();
        $data = $this->payload();
        $device = BiometricDevice::create($data);
        $this->actingAs(User::factory()->create());
        $this->get(route('hr.device.index'))->assertForbidden();
        $this->get(route('hr.device.edit', $device))->assertForbidden();
        $this->postJson(route('hr.device.store'), $data)->assertForbidden();
        $this->putJson(route('hr.device.update', $device), $data)->assertForbidden();
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();
        $this->actingAs($admin)->withoutVite()->get(route('hr.device.index'))->assertOk();
    }

    public function test_editing_settings_preserves_bridge_telemetry(): void
    {
        $data = $this->payload();
        $device = BiometricDevice::create($data);
        $device->forceFill(['status' => 'Online', 'last_sync_at' => '2026-10-30 06:00:00'])->save();
        $this->put(route('hr.device.update', $device), $data + ['status' => 'Offline', 'last_sync_at' => null])->assertRedirect();
        $this->assertSame('Online', $device->fresh()->status);
        $this->assertSame('2026-10-30 06:00:00', $device->fresh()->last_sync_at->format('Y-m-d H:i:s'));
        $this->withoutVite()->get(route('hr.device.index'))->assertOk()->assertSee('2026-10-30 11:30');
    }
}
