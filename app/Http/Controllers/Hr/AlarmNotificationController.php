<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\BiometricStagingRead;
use App\Models\BiometricStagingUser;
use App\Models\Employee;
use App\Models\SystemAlarm;
use App\Models\SystemAlarmRead;
use App\Services\EmployeeDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlarmNotificationController extends Controller
{
    private function active()
    {
        return SystemAlarm::whereNull('resolved_at')->whereHas('document.employee');
    }

    public function index(Request $request, EmployeeDocumentService $documents)
    {
        $canDocuments = $request->user()->canManageEmployeeDocuments();
        $canEnrollments = $request->user()->canReviewBiometricEnrollments();
        abort_unless($canDocuments || $canEnrollments, 403);
        $request->validate(['page' => ['nullable', 'integer', 'min:1']]);
        if ($canDocuments) {
            $documents->refreshAlarms();
        }
        $matches = fn ($query) => $query->where('user_id', $request->user()->id)
            ->whereColumn('system_alarm_reads.status', 'system_alarms.status')
            ->whereColumn('system_alarm_reads.due_on', 'system_alarms.due_on');
        $enrollmentReads = fn ($query) => $query->where('user_id', $request->user()->id);
        $alarmQuery = $this->active()->when(! $canDocuments, fn ($query) => $query->whereRaw('1 = 0'));
        $enrollmentQuery = BiometricStagingUser::where('status', BiometricStagingUser::PENDING)
            ->when(! $canEnrollments, fn ($query) => $query->whereRaw('1 = 0'));
        $unread = (clone $alarmQuery)->whereDoesntHave('reads', $matches)->count()
            + (clone $enrollmentQuery)->whereDoesntHave('notificationReads', $enrollmentReads)->count();

        // Paginate both types together, rather than hiding one type behind a separate list.
        $candidates = (clone $alarmQuery)->selectRaw("id, 'document' as kind, created_at as occurred_at")
            ->unionAll((clone $enrollmentQuery)->selectRaw("id, 'enrollment' as kind, created_at as occurred_at"));
        $page = DB::query()->fromSub($candidates, 'notification_feed')
            ->orderByDesc('occurred_at')->orderBy('kind')->orderByDesc('id')->paginate(10);
        $alarms = SystemAlarm::with(['document.employee', 'document.documentType'])
            ->withExists(['reads as is_read' => $matches])
            ->whereIn('id', $page->getCollection()->where('kind', 'document')->pluck('id'))->get()->keyBy('id');
        $enrollments = BiometricStagingUser::with(['device', 'branch'])
            ->withExists(['notificationReads as is_read' => $enrollmentReads])
            ->whereIn('id', $page->getCollection()->where('kind', 'enrollment')->pluck('id'))->get()->keyBy('id');
        $notifications = $page->getCollection()->map(function ($item) use ($alarms, $enrollments) {
            if ($item->kind === 'enrollment') {
                $row = $enrollments->get($item->id);
                if (! $row || $row->status !== BiometricStagingUser::PENDING) {
                    return null;
                }

                return [
                    'id' => $row->id, 'type' => 'enrollment', 'is_read' => (bool) $row->is_read,
                    'status' => $row->status, 'enrollment_id' => $row->raw_enrollment_id,
                    'name' => $row->raw_name ?: 'Unnamed enrollment', 'device' => $row->device->name,
                    'branch' => $row->branch->name, 'open_url' => route('hr.person.notifications.enrollment.open', $row),
                ];
            }
            $alarm = $alarms->get($item->id);
            if (! $alarm || $alarm->resolved_at !== null || ! $alarm->document?->employee) {
                return null;
            }

            return [
                'id' => $alarm->id, 'type' => 'document', 'is_read' => (bool) $alarm->is_read,
                'status' => $alarm->status, 'due_on' => $alarm->due_on->toDateString(),
                'employee_id' => $alarm->document->employee->employee_id,
                'employee_name' => $alarm->document->employee->name ?: trim($alarm->document->employee->first_name.' '.$alarm->document->employee->last_name),
                'document_type' => $alarm->document->documentType->name, 'file_name' => $alarm->document->name,
                'open_url' => route('hr.person.notifications.open', $alarm),
            ];
        })->filter()->values();

        return response()->json([
            'unread_count' => $unread, 'total' => $page->total(), 'next_page_url' => $page->nextPageUrl(),
            'notifications' => $notifications,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function openEnrollment(Request $request, BiometricStagingUser $staging)
    {
        abort_unless($request->user()->canReviewBiometricEnrollments(), 403);
        $staging->load('device');
        if ($staging->status !== BiometricStagingUser::PENDING) {
            return to_route('hr.person.to-be-reviewed', ['status' => $staging->status, 'search' => $staging->raw_enrollment_id]);
        }
        BiometricStagingRead::updateOrCreate([
            'user_id' => $request->user()->id, 'biometric_staging_user_id' => $staging->id,
        ], ['read_at' => now()]);
        if (! $staging->device->is_active || Employee::withTrashed()->where('employee_id', $staging->raw_enrollment_id)->exists()) {
            return to_route('hr.person.to-be-reviewed', ['search' => $staging->raw_enrollment_id]);
        }

        return to_route('hr.person.create', ['staging' => $staging->id]);
    }

    public function open(Request $request, SystemAlarm $alarm, EmployeeDocumentService $documents)
    {
        abort_unless($request->user()->canManageEmployeeDocuments(), 403);
        $alarm->load(['document.employee', 'document.documentType']);
        abort_unless($alarm->document?->employee, 404);
        $documents->refreshAlarm($alarm->document);
        $alarm->refresh();
        abort_unless($alarm->resolved_at === null, 404);
        SystemAlarmRead::updateOrCreate(['user_id' => $request->user()->id, 'system_alarm_id' => $alarm->id], [
            'status' => $alarm->status, 'due_on' => $alarm->due_on->toDateString(), 'read_at' => now(),
        ]);
        $employee = $alarm->document->employee;
        $ids = $request->session()->get('person_registration_ids', []);
        $request->session()->put('person_registration_ids', array_values(array_unique([...$ids, $employee->id])));

        return to_route('hr.person.create', [
            'employee' => $employee->id, 'tab' => 'documents', 'document_type' => $alarm->document->documentType->code,
            'document_file' => $alarm->document->file_id,
        ]);
    }
}
