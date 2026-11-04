<?php

namespace Tests\Feature;

use App\Models\BiometricDevice;
use App\Models\BiometricStagingUser;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiometricEnrollmentApiTest extends TestCase
{
    use RefreshDatabase;

    private function device(): BiometricDevice
    {
        $branch = Branch::firstOrCreate(['branch_code' => 'HQ'], ['name' => 'Head Office']);
        $device = BiometricDevice::create(['name' => 'Entrance', 'brand' => 'Hikvision', 'serial_number' => bin2hex(random_bytes(6)),
            'branch_id' => $branch->id, 'ip_address' => '192.168.1.100', 'port' => 8000, 'timezone' => 'Asia/Colombo', 'is_active' => true]);
        $device->forceFill(['bridge_token_hash' => hash('sha256', str_repeat('a', 64)), 'bridge_token_expires_at' => now()->addDay()])->save();
        return $device;
    }

    private function url(BiometricDevice $device): string
    {
        return route('api.biometric.enrollments.store', $device->id);
    }

    private function payload(): array
    {
        return ['raw_enrollment_id' => '00025', 'raw_name' => 'Kamal', 'fetched_at' => now()->subMinute()->toIso8601String()];
    }

    public function test_bridge_stages_enrollment_without_creating_employee_and_it_appears_in_notifications(): void
    {
        $device = $this->device();
        $this->withToken(str_repeat('a', 64))->postJson($this->url($device), $this->payload())
            ->assertCreated()->assertJsonPath('data.raw_enrollment_id', '00025')->assertJsonPath('data.created', true);
        $this->assertDatabaseHas('biometric_staging_users', ['device_id' => $device->id, 'branch_id' => $device->branch_id,
            'raw_enrollment_id' => '00025', 'source' => 'Bridge', 'status' => 'Pending HR Review']);
        $this->assertDatabaseCount('employees', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertNull($device->fresh()->last_sync_at);
        $hr = User::factory()->create();
        $hr->forceFill(['role' => 'hr'])->save();
        $this->actingAs($hr)->getJson(route('hr.person.notifications.index'))->assertOk()->assertJsonPath('total', 1);
    }

    public function test_retry_retains_review_decision_name_and_newest_fetch_time(): void
    {
        $device = $this->device();
        $payload = $this->payload();
        $this->withToken(str_repeat('a', 64))->postJson($this->url($device), $payload)->assertCreated();
        $row = BiometricStagingUser::firstOrFail();
        foreach (['Ignored', 'Promoted'] as $status) {
            $row->forceFill(['status' => $status, 'ignore_reason' => 'Test'])->save();
            $this->postJson($this->url($device), array_replace($payload, ['raw_name' => 'Changed', 'fetched_at' => now()->subDay()->toIso8601String()]))
                ->assertOk()->assertJsonPath('data.created', false)->assertJsonPath('data.status', $status);
            $this->assertSame('Kamal', $row->fresh()->raw_name);
            $this->assertSame($payload['fetched_at'], $row->fresh()->fetched_at->toIso8601String());
        }
        $this->assertDatabaseCount('biometric_staging_users', 1);
    }

    public function test_credentials_are_required_even_for_logged_in_admin_and_scoped_to_device(): void
    {
        $device = $this->device();
        $this->postJson($this->url($device), $this->payload())->assertUnauthorized();
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin)->postJson($this->url($device), $this->payload())->assertUnauthorized();
        $this->withToken(str_repeat('b', 64))->postJson($this->url($device), $this->payload())->assertUnauthorized();
        $other = $this->device();
        $other->forceFill(['bridge_token_hash' => hash('sha256', str_repeat('c', 64))])->save();
        $this->withToken(str_repeat('a', 64))->postJson($this->url($other), $this->payload())->assertUnauthorized();
        $device->forceFill(['bridge_token_expires_at' => now()->subSecond()])->save();
        $this->postJson($this->url($device), $this->payload())->assertUnauthorized();
        $device->forceFill(['bridge_token_expires_at' => now()->addDay(), 'is_active' => false])->save();
        $this->postJson($this->url($device), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('biometric_staging_users', 0);
    }

    public function test_validation_and_nullable_name(): void
    {
        $device = $this->device();
        $this->withToken(str_repeat('a', 64));
        foreach ([['raw_enrollment_id' => 25], ['raw_enrollment_id' => 'bad id'], ['fetched_at' => '2026-01-01 09:00:00'],
            ['fetched_at' => now()->addDay()->toIso8601String()], ['branch_id' => 123], ['status' => 'Promoted']] as $invalid) {
            $this->postJson($this->url($device), array_replace($this->payload(), $invalid))->assertUnprocessable();
        }
        $this->assertDatabaseCount('biometric_staging_users', 0);
        $this->postJson($this->url($device), array_replace($this->payload(), ['raw_name' => null]))->assertCreated();
        $this->assertNull(BiometricStagingUser::first()->raw_name);
    }

    public function test_endpoint_is_rate_limited(): void
    {
        $device = $this->device();
        $this->withToken(str_repeat('a', 64));
        for ($i = 0; $i < 60; $i++) {
            $this->postJson($this->url($device), $this->payload())->assertStatus($i === 0 ? 201 : 200);
        }
        $this->postJson($this->url($device), $this->payload())->assertStatus(429);
        $this->assertDatabaseCount('biometric_staging_users', 1);
    }

    public function test_token_command_rotation_and_revocation(): void
    {
        $device = $this->device();
        $oldHash = $device->bridge_token_hash;
        $this->artisan('biometric:bridge-token', ['device' => $device->id, '--days' => 30])->assertSuccessful();
        $this->assertNotSame($oldHash, $device->fresh()->bridge_token_hash);
        $this->assertArrayNotHasKey('bridge_token_hash', $device->fresh()->toArray());
        $this->withToken(str_repeat('a', 64))->postJson($this->url($device), $this->payload())->assertUnauthorized();
        $this->artisan('biometric:bridge-token', ['device' => $device->id, '--revoke' => true])->assertSuccessful();
        $this->assertNull($device->fresh()->bridge_token_hash);
        $this->postJson($this->url($device), $this->payload())->assertUnauthorized();
        $this->artisan('biometric:bridge-token', ['device' => $device->id, '--days' => 0])->assertFailed();
    }
}
