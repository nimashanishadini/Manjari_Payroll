<?php

namespace App\Services;

use App\Models\BiometricDevice;
use Illuminate\Http\Request;

class DeviceBridgeAuthenticator
{
    /** Call within a transaction: serializes imports and credential changes. */
    public function authenticate(Request $request, string $deviceId): BiometricDevice
    {
        $device = BiometricDevice::whereKey($deviceId)->lockForUpdate()->first();
        $token = $request->bearerToken();
        abort_unless($device && $token && strlen($token) === 64
            && $device->bridge_token_hash
            && hash_equals($device->bridge_token_hash, hash('sha256', $token))
            && $device->bridge_token_expires_at?->isFuture(), 401, 'Invalid or expired bridge credentials.');
        abort_unless($device->is_active, 403, 'This device is disabled.');
        return $device;
    }
}
