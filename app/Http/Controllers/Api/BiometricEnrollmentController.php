<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BiometricOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BiometricEnrollmentController extends Controller
{
    public function __invoke(Request $request, string $deviceId, BiometricOnboardingService $onboarding): JsonResponse
    {
        return DB::transaction(function () use ($request, $deviceId, $onboarding) {
            $device = app(\App\Services\DeviceBridgeAuthenticator::class)->authenticate($request, $deviceId);

            // Accept metadata only. Ownership, review state and branch are server controlled.
            $data = Validator::make($request->all(), [
                'raw_enrollment_id' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+$/'],
                'raw_name' => ['nullable', 'string', 'max:255'],
                'fetched_at' => ['required', 'date', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/', 'before_or_equal:now'],
                'branch_id' => ['prohibited'],
                'branch_code' => ['prohibited'],
                'status' => ['prohibited'],
                'promoted_employee_id' => ['prohibited'],
            ])->validate();

            $row = $onboarding->stage($device, $data['raw_enrollment_id'], $data['raw_name'] ?? null,
                Carbon::parse($data['fetched_at'])->utc(), 'Bridge');
            $created = $row->wasRecentlyCreated;

            return response()->json(['data' => [
                'id' => $row->id,
                'device_id' => $row->device_id,
                'raw_enrollment_id' => $row->raw_enrollment_id,
                'status' => $row->status,
                'created' => $created,
                'fetched_at' => $row->fetched_at->utc()->toIso8601String(),
            ]], $created ? 201 : 200)->header('Cache-Control', 'no-store');
        }, 3);
    }
}
