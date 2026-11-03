<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\RawAttendance;
use App\Services\DeviceBridgeAuthenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttendancePunchController extends Controller
{
    public function __invoke(Request $request, string $deviceId, DeviceBridgeAuthenticator $auth): JsonResponse
    {
        return DB::transaction(function () use ($request, $deviceId, $auth) {
            $device = $auth->authenticate($request, $deviceId);
            $data = Validator::make($request->all(), [
                'employee_id' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+$/'],
                'punch_time' => ['required', 'date', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/', 'before_or_equal:now'],
                'verify_type' => ['nullable', 'string', 'max:255'],
                'mask_status' => ['nullable', 'string', 'max:255'],
                'device_id' => ['prohibited'], 'branch_code' => ['prohibited'], 'branch_id' => ['prohibited'],
                'source' => ['prohibited'], 'source_request_id' => ['prohibited'],
                'punch_state' => ['prohibited'], 'state_source' => ['prohibited'], 'is_anomalous' => ['prohibited'],
            ])->validate();
            // Historical punches remain valid for soft-deleted or resigned employees.
            $employee = Employee::withTrashed()->where('employee_id', $data['employee_id'])->lockForUpdate()->first();
            if (! $employee) {
                return response()->json([
                    'message' => 'Employee is not registered yet. Retain this punch and resend after HR promotion.',
                    'code' => 'employee_not_registered', 'retryable' => true, 'stored' => false,
                ], 409)->header('Cache-Control', 'no-store');
            }
            $row = RawAttendance::firstOrCreate([
                'device_id' => $device->id, 'employee_id' => $employee->employee_id,
                'punch_time' => Carbon::parse($data['punch_time'])->utc()->format('Y-m-d H:i:s.u'),
            ], [
                'branch_code' => $device->branch->branch_code, 'source' => 'Device',
                'verify_type' => $data['verify_type'] ?? null, 'mask_status' => $data['mask_status'] ?? null,
            ]);
            return response()->json(['data' => [
                'id' => $row->id, 'device_id' => $row->device_id, 'employee_id' => $row->employee_id,
                'branch_code' => $row->branch_code, 'punch_time' => $row->punch_time->utc()->format('Y-m-d\TH:i:s.u\Z'),
                'source' => $row->source, 'created' => $row->wasRecentlyCreated,
            ]], $row->wasRecentlyCreated ? 201 : 200)->header('Cache-Control', 'no-store');
        }, 3);
    }
}
