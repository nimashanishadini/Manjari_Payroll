<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\SystemAlarm;
use App\Models\SystemAlarmRead;
use App\Services\EmployeeDocumentService;
use Illuminate\Http\Request;

class AlarmNotificationController extends Controller
{
    private function active()
    {
        return SystemAlarm::whereNull('resolved_at')->whereHas('document.employee');
    }

    public function index(Request $request, EmployeeDocumentService $documents)
    {
        abort_unless($request->user()->canManageEmployeeDocuments(), 403);
        $request->validate(['page' => ['nullable', 'integer', 'min:1']]);
        $documents->refreshAlarms();
        $matches = fn ($query) => $query->where('user_id', $request->user()->id)
            ->whereColumn('system_alarm_reads.status', 'system_alarms.status')
            ->whereColumn('system_alarm_reads.due_on', 'system_alarms.due_on');
        $unread = $this->active()->whereDoesntHave('reads', $matches)->count();
        $alarms = $this->active()->with(['document.employee', 'document.documentType'])
            ->withExists(['reads as is_read' => $matches])->orderBy('due_on')->orderBy('id')->paginate(10);

        return response()->json([
            'unread_count' => $unread,
            'total' => $alarms->total(),
            'next_page_url' => $alarms->nextPageUrl(),
            'notifications' => $alarms->getCollection()->map(fn ($alarm) => [
                'id' => $alarm->id, 'is_read' => (bool) $alarm->is_read,
                'status' => $alarm->status, 'due_on' => $alarm->due_on->toDateString(),
                'employee_id' => $alarm->document->employee->employee_id,
                'employee_name' => $alarm->document->employee->name ?: trim($alarm->document->employee->first_name.' '.$alarm->document->employee->last_name),
                'document_type' => $alarm->document->documentType->name, 'file_name' => $alarm->document->name,
                'open_url' => route('hr.person.notifications.open', $alarm),
            ])->values(),
        ])->header('Cache-Control', 'private, no-store');
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
