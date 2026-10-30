<?php

namespace App\Services;

use App\Models\DocumentTypeMaster;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\SystemAlarm;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentService
{
    // Called inside the employee's locked save transaction. JSON remains a compatibility mirror.
    public function sync(Employee $employee): void
    {
        $types = DocumentTypeMaster::all()->keyBy('code');
        $ids = [];
        foreach ($employee->documents ?? [] as $code => $category) {
            if (! isset($types[$code])) {
                continue;
            }
            foreach ($category['files'] ?? [] as $file) {
                $document = EmployeeDocument::updateOrCreate(
                    ['employee_id' => $employee->id, 'file_id' => $file['id']],
                    ['document_type_master_id' => $types[$code]->id, 'disk' => $file['disk'] ?? 'local',
                        'path' => $file['path'], 'name' => $file['name'], 'expires_on' => $file['expires_on'] ?? null]
                );
                $ids[] = $document->id;
                $this->refreshAlarm($document->setRelation('documentType', $types[$code]));
            }
        }
        $employee->employeeDocuments()->whereNotIn('id', $ids)->delete();
        if ($this->onboardingStatus($employee)['blocking_types']) {
            $employee->forceFill(['onboarding_completed_at' => null])->save();
        }
    }

    public function onboardingStatus(Employee $employee): array
    {
        $documents = $employee->employeeDocuments()->get()->groupBy('document_type_master_id');
        $blocking = DocumentTypeMaster::where('is_mandatory', true)->get()->filter(function ($type) use ($documents) {
            return ! ($documents[$type->id] ?? collect())->contains(function ($document) use ($type) {
                if ($type->requires_expiry && ! $document->expires_on) {
                    return false;
                }
                if ($type->requires_expiry && $document->expires_on && $document->expires_on->toDateString() < today(config('document_management.timezone'))->toDateString()) {
                    return false;
                }

                return Storage::disk($document->disk)->exists($document->path);
            });
        })->pluck('name')->values()->all();

        return ['completed' => $employee->onboarding_completed_at !== null && ! $blocking,
            'can_complete' => ! $blocking, 'blocking_types' => $blocking];
    }

    public function refreshAlarm(EmployeeDocument $document): void
    {
        $expiry = $document->expires_on;
        $active = $document->employee()->exists();
        if ($active && $document->documentType->requires_expiry && $expiry && $expiry->toDateString() <= today(config('document_management.timezone'))->addDays($document->documentType->reminder_days)->toDateString()) {
            SystemAlarm::updateOrCreate(['employee_document_id' => $document->id], [
                'status' => $expiry->toDateString() < today(config('document_management.timezone'))->toDateString() ? 'expired' : 'expiring',
                'due_on' => $expiry->toDateString(), 'resolved_at' => null,
            ]);
        } else {
            SystemAlarm::where('employee_document_id', $document->id)->whereNull('resolved_at')->update(['resolved_at' => now()]);
        }
    }

    public function refreshAlarms(): void
    {
        EmployeeDocument::with('documentType')->chunkById(100, function ($documents) {
            foreach ($documents as $document) {
                $this->refreshAlarm($document);
            }
        });
        Employee::whereNotNull('onboarding_completed_at')->chunkById(100, function ($employees) {
            foreach ($employees as $employee) {
                if ($this->onboardingStatus($employee)['blocking_types']) {
                    $employee->forceFill(['onboarding_completed_at' => null])->save();
                }
            }
        });
    }
}
