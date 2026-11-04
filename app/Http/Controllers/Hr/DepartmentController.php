<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeManagement($request);
        return to_route('hr.person.index');
    }

    public function store(Request $request)
    {
        $this->authorizeManagement($request);
        Department::create($request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('departments')->where('branch_id', $request->input('branch_id'))],
        ]));
        return to_route('hr.person.index')->with('status', 'Department created successfully.');
    }

    public function update(Request $request, Department $department)
    {
        $this->authorizeManagement($request);
        $data = $request->validate([
            // Reparenting would rewrite historical branch reporting and device ownership.
            'branch_id' => ['sometimes', 'required', Rule::in([$department->branch_id])],
            'name' => ['required', 'string', 'max:255', Rule::unique('departments')->where('branch_id', $department->branch_id)->ignore($department->id)],
        ]);
        $department->update(['name' => $data['name']]);
        return to_route('hr.person.index')->with('status', 'Department updated successfully.');
    }

    public function destroy(Request $request, Department $department)
    {
        $this->authorizeManagement($request);
        try {
            DB::transaction(function () use ($department) {
                $locked = Department::whereKey($department->id)->lockForUpdate()->firstOrFail();
                if (Employee::withTrashed()->where('department_id', $locked->id)->exists()
                    || $locked->biometricDevices()->exists() || $locked->employmentHistories()->exists()) {
                    throw ValidationException::withMessages(['department' => 'This department is in use by employees, devices or employment history and cannot be deleted.']);
                }
                $locked->delete();
            });
        } catch (QueryException $error) {
            if (! in_array((string) $error->getCode(), ['23000', '23503'], true)) throw $error;
            throw ValidationException::withMessages(['department' => 'This department is referenced by other records and cannot be deleted.']);
        }
        return to_route('hr.person.index')->with('status', 'Unused department deleted.');
    }

    private function authorizeManagement(Request $request): void
    {
        abort_unless($request->user()?->canManageEmploymentStructure(), 403);
    }
}
