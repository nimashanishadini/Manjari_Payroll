<?php

namespace App\Services;

use App\Models\BiometricDevice;
use App\Models\BiometricStagingUser;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BiometricOnboardingService
{
    public function stage(BiometricDevice $device, string $enrollmentId, ?string $name = null, ?CarbonInterface $fetchedAt = null): BiometricStagingUser
    {
        return DB::transaction(function () use ($device, $enrollmentId, $name, $fetchedAt) {
            $locked = BiometricDevice::whereKey($device->id)->lockForUpdate()->firstOrFail();
            if (! $locked->is_active) {
                throw ValidationException::withMessages(['device_id' => 'Choose an enabled device.']);
            }

            // A repeated import never resets a reviewed row or creates another row.
            $row = BiometricStagingUser::firstOrCreate(
                ['device_id' => $locked->id, 'raw_enrollment_id' => $enrollmentId],
                ['raw_name' => trim($name ?? '') ?: null, 'branch_id' => $locked->branch_id, 'source' => 'Manual', 'fetched_at' => $fetchedAt ?? now()]
            );
            $fetchTime = $fetchedAt ?? now();
            if (! $row->wasRecentlyCreated && $fetchTime->gt($row->fetched_at)) {
                $row->forceFill(['fetched_at' => $fetchTime])->save();
            }

            return $row;
        });
    }

    public function promote(BiometricStagingUser $staging, array $data, User $reviewer): Employee
    {
        try {
            return DB::transaction(function () use ($staging, $data, $reviewer) {
                $locked = BiometricStagingUser::whereKey($staging->id)->lockForUpdate()->firstOrFail();
                $this->requirePending($locked);
                $device = BiometricDevice::whereKey($locked->device_id)->lockForUpdate()->firstOrFail();
                if (! $device->is_active) {
                    throw ValidationException::withMessages(['device' => 'This device is disabled. Enable it before promoting an enrollment.']);
                }
                if (Employee::withTrashed()->where('employee_id', $locked->raw_enrollment_id)->exists()) {
                    throw ValidationException::withMessages(['employee_id' => 'An employee already uses this enrollment ID. A duplicate cannot be promoted.']);
                }
                $designation = Designation::whereKey($data['designation_id'])->lockForUpdate()->firstOrFail();
                if (! $designation->is_active) {
                    throw ValidationException::withMessages(['designation_id' => 'Select an active designation.']);
                }
                $user = null;
                if ($data['create_login'] ?? false) {
                    $user = User::create(['name' => $data['name'], 'email' => $data['login_email'], 'password' => $data['login_password']]);
                    $user->forceFill(['role' => 'employee'])->save();
                }
                $names = preg_split('/\s+/', trim($data['name']), 2);
                $employee = Employee::create(Arr::only($data, [
                    'name', 'department_id', 'joined_date', 'employment_type', 'probation_end_date', 'confirmed_date',
                    'nic_number', 'email', 'phone', 'profile_photo',
                ]) + [
                    'employee_id' => $locked->raw_enrollment_id, 'first_name' => $names[0], 'last_name' => $names[1] ?? '',
                    'branch_id' => $locked->branch_id, 'branch_code' => $locked->branch->branch_code,
                    'line_manager_id' => $data['manager_id'] ?? null, 'user_id' => $user?->id,
                    'allow_self_service' => $user !== null, 'status' => 'Active',
                ]);
                $employee->employmentHistories()->create([
                    'department_id' => $employee->department_id, 'designation_id' => $designation->id,
                    'manager_id' => $employee->line_manager_id, 'start_date' => $data['joined_date'],
                    'end_date' => null, 'change_reason' => 'Biometric onboarding',
                ]);
                $locked->forceFill(['status' => BiometricStagingUser::PROMOTED, 'promoted_employee_id' => $employee->id,
                    'reviewed_by' => $reviewer->id, 'reviewed_at' => now()])->save();

                return $employee;
            }, 3);
        } catch (UniqueConstraintViolationException $error) {
            if (Employee::withTrashed()->where('employee_id', $staging->raw_enrollment_id)->exists()) {
                throw ValidationException::withMessages(['employee_id' => 'An employee already uses this enrollment ID.']);
            }
            if (! empty($data['login_email']) && User::where('email', $data['login_email'])->exists()) {
                throw ValidationException::withMessages(['login_email' => 'This email address already has a login account.']);
            }
            throw $error;
        }
    }

    public function ignore(BiometricStagingUser $staging, User $reviewer, string $reason): void
    {
        DB::transaction(function () use ($staging, $reviewer, $reason) {
            $locked = BiometricStagingUser::whereKey($staging->id)->lockForUpdate()->firstOrFail();
            $this->requirePending($locked);
            $locked->forceFill(['status' => BiometricStagingUser::IGNORED, 'ignore_reason' => $reason,
                'reviewed_by' => $reviewer->id, 'reviewed_at' => now()])->save();
        });
    }

    private function requirePending(BiometricStagingUser $staging): void
    {
        if ($staging->status !== BiometricStagingUser::PENDING) {
            throw ValidationException::withMessages(['enrollment' => 'This enrollment has already been reviewed.']);
        }
    }
}
