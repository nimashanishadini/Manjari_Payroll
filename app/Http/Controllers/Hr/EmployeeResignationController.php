<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeResignationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'hr'], true), 403);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $employees = Employee::with('department')->where('status', 'Resigned')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")->orWhere('employee_id', 'like', "%{$search}%");
                });
            })->orderByDesc('resigned_on')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('hr.person.resigned', ['employees' => $employees, 'search' => $filters['search'] ?? '']);
    }

    public function store(Request $request, Employee $employee)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate([
            'resigned_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Colombo')->toDateString()],
            'resignation_reason' => ['required', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($employee, $request, $data) {
            $locked = Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'Resigned') {
                throw ValidationException::withMessages(['employee' => 'This employee is already marked as resigned.']);
            }
            if ($locked->joined_date && $data['resigned_on'] < substr((string) $locked->joined_date, 0, 10)) {
                throw ValidationException::withMessages(['resigned_on' => 'The resignation date cannot be before the employment date.']);
            }
            $locked->forceFill($data + ['status' => 'Resigned', 'resigned_by' => $request->user()->id, 'allow_self_service' => false])->save();
        });

        return response()->json(['id' => $employee->id, 'status' => 'Resigned', 'resigned_on' => $data['resigned_on'],
            'resignation_reason' => $data['resignation_reason'], 'message' => 'Employee marked as resigned.']);
    }
}
