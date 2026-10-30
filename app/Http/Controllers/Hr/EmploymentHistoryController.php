<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmploymentHistoryController extends Controller
{
    public function index(int $employeeId): JsonResponse
    {
        return response()->json(['data' => Employee::findOrFail($employeeId)->employmentHistories()
            ->with(['department', 'designation', 'manager'])->orderByDesc('start_date')->get()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, int $employeeId): JsonResponse
    {
        return $this->save($request, $employeeId, false);
    }

    public function transfer(Request $request, int $employeeId): JsonResponse
    {
        return $this->save($request, $employeeId, true);
    }

    private function save(Request $request, int $employeeId, bool $transfer): JsonResponse
    {
        abort_unless($request->user()->canManageEmploymentStructure(), 403);
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('branch_id', $request->input('branch_id'))],
            'designation_id' => ['required', 'integer', Rule::exists('designations', 'id')],
            'manager_id' => ['present', 'nullable', 'integer', Rule::exists('employees', 'id')->whereNull('deleted_at')->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'Resigned')), Rule::notIn([$employeeId])],
            'start_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'end_date' => [$transfer ? 'prohibited' : 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:today'],
            'change_reason' => ['required', 'string', 'max:255'],
        ]);
        $history = DB::transaction(function () use ($employeeId, $data, $transfer) {
            // All period changes for one employee serialize through this lock.
            $employee = Employee::whereKey($employeeId)->lockForUpdate()->firstOrFail();
            if ($employee->status === 'Resigned') {
                throw ValidationException::withMessages(['employee' => 'Employment periods cannot be changed for a resigned employee.']);
            }
            $start = Carbon::parse($data['start_date']);
            if ($employee->joined_date && $start->lt(Carbon::parse($employee->joined_date)->startOfDay())) {
                throw ValidationException::withMessages(['start_date' => 'The period cannot start before the employee joined.']);
            }
            $current = $employee->employmentHistories()->whereNull('end_date')->lockForUpdate()->get();
            if ($current->count() > 1) {
                throw ValidationException::withMessages(['start_date' => 'Multiple current periods exist. Correct the existing history before continuing.']);
            }
            $previous = $current->first();
            $designation = Designation::whereKey($data['designation_id'])->lockForUpdate()->firstOrFail();
            if (! $designation->is_active && ! ($transfer && $previous && (int) $previous->designation_id === (int) $designation->id)) {
                throw ValidationException::withMessages(['designation_id' => 'Choose an active designation for a new assignment.']);
            }
            if ($transfer || empty($data['end_date'])) {
                $visited = [$employee->id];
                $managerId = $data['manager_id'];
                while ($managerId !== null) {
                    if (in_array((int) $managerId, $visited, true)) {
                        throw ValidationException::withMessages(['manager_id' => 'The reporting manager would create a circular management chain.']);
                    }
                    $visited[] = (int) $managerId;
                    $managerId = Employee::whereKey($managerId)->value('line_manager_id');
                }
            }

            if ($transfer) {
                if (! $previous) {
                    throw ValidationException::withMessages(['start_date' => 'Create the initial current period first.']);
                }
                if ($start->lte($previous->start_date)) {
                    throw ValidationException::withMessages(['start_date' => 'The new period must start after the current period started.']);
                }
                if ((int) $previous->department_id === (int) $data['department_id']
                    && (int) $previous->designation_id === (int) $data['designation_id']
                    && $previous->manager_id === ($data['manager_id'] === null ? null : (int) $data['manager_id'])) {
                    throw ValidationException::withMessages(['department_id' => 'Change the department, designation or reporting manager to create a new period.']);
                }
            } elseif ($previous && empty($data['end_date'])) {
                throw ValidationException::withMessages(['end_date' => 'A current period already exists. Use Promotion / Transfer instead.']);
            }
            $overlap = $employee->employmentHistories()
                ->when($transfer, fn ($query) => $query->where('id', '!=', $previous->id))
                ->where(fn ($query) => $query->whereNull('end_date')->orWhere('end_date', '>=', $data['start_date']))
                ->when(! empty($data['end_date']), fn ($query) => $query->where('start_date', '<=', $data['end_date']))
                ->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['start_date' => 'This period overlaps an existing employment period.']);
            }
            if ($transfer) {
                $previous->update(['end_date' => $start->copy()->subDay()->toDateString()]);
            }
            $history = $employee->employmentHistories()->create([
                'department_id' => $data['department_id'], 'designation_id' => $data['designation_id'],
                'manager_id' => $data['manager_id'], 'start_date' => $data['start_date'],
                'end_date' => $transfer ? null : ($data['end_date'] ?? null), 'change_reason' => $data['change_reason'],
            ]);
            if ($history->end_date === null) {
                $employee->update(['branch_id' => $data['branch_id'], 'department_id' => $data['department_id'],
                    'branch_code' => DB::table('branches')->where('id', $data['branch_id'])->value('branch_code'),
                    'line_manager_id' => $data['manager_id']]);
            }

            return $history;
        });

        return response()->json(['message' => 'Employment period saved.', 'data' => $history->load(['department', 'designation', 'manager'])], 201)
            ->header('Cache-Control', 'private, no-store');
    }
}
