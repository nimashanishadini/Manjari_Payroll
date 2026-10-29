<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
   
public function index(Request $request): JsonResponse
{
    $validated = $request->validate([
        'search' => ['nullable', 'string', 'max:255'],
        'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        'employment_type' => [
            'nullable',
            Rule::in(['Probation', 'Permanent', 'Contract', 'Intern']),
        ],
        'status' => ['nullable', 'string', 'max:50'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
    ]);

    $query = Employee::with([
        'branch',
        'department',
        'lineManager',
    ]);

    // Search by employee ID, name, first name, last name or email
    if (! empty($validated['search'])) {
        $search = $validated['search'];

        $query->where(function ($q) use ($search) {
            $q->where('employee_id', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('personal_email', 'like', "%{$search}%");
        });
    }

    // Filters
    if (isset($validated['branch_id'])) {
        $query->where('branch_id', $validated['branch_id']);
    }

    if (isset($validated['department_id'])) {
        $query->where('department_id', $validated['department_id']);
    }

    if (isset($validated['employment_type'])) {
        $query->where('employment_type', $validated['employment_type']);
    }

    if (isset($validated['status'])) {
        $query->where('status', $validated['status']);
    }

    $perPage = $validated['per_page'] ?? 10;

    $employees = $query
        ->orderByDesc('id')
        ->paginate($perPage)
        ->withQueryString();

    return response()->json($employees);
}


    
public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'employee_id' => [
            'required',
            'string',
            'max:255',
            'unique:employees,employee_id',
        ],

        'first_name' => ['required', 'string', 'max:255'],
        'last_name' => ['required', 'string', 'max:255'],

        'email' => [
            'nullable',
            'email',
            'max:255',
            'unique:employees,email',
        ],

        'phone' => ['nullable', 'string', 'max:30'],

        'branch_id' => [
            'required',
            'integer',
            'exists:branches,id',
        ],

        'department_id' => [
            'required',
            'integer',
            Rule::exists('departments', 'id')
                ->where('branch_id', $request->input('branch_id')),
        ],

        'line_manager_id' => [
            'nullable',
            'integer',
            'exists:employees,id',
            Rule::notIn(array_filter([$request->input('id')])),
        ],

        'user_id' => [
            'nullable',
            'integer',
            'exists:users,id',
        ],

        'employment_type' => [
            'required',
            Rule::in([
                'Probation',
                'Permanent',
                'Contract',
                'Intern',
            ]),
        ],

        'probation_end_date' => ['nullable', 'date'],
        'confirmed_date' => ['nullable', 'date'],

        // Additional ERD fields
        'name' => ['nullable', 'string', 'max:255'],
        'nic_number' => ['nullable', 'string', 'max:50'],
        'date_of_birth' => ['nullable', 'date'],
        'gender' => ['nullable', 'string', 'max:30'],

        'contact_number' => ['nullable', 'string', 'max:30'],
        'personal_email' => ['nullable', 'email', 'max:255'],
        'residential_address' => ['nullable', 'string'],

        'emergency_contact_name' => [
            'nullable',
            'string',
            'max:255',
        ],

        'emergency_contact_phone' => [
            'nullable',
            'string',
            'max:30',
        ],

        'branch_code' => ['nullable', 'string', 'max:255'],
        'joined_date' => ['nullable', 'date'],
        'status' => ['nullable', 'string', 'max:50'],
    ]);

    $employee = Employee::create($validated);

    return response()->json([
        'message' => 'Employee created successfully.',
        'data' => $employee->load([
            'branch',
            'department',
            'lineManager',
        ]),
    ], 201);
}


public function show(int $id): JsonResponse
{
    $employee = Employee::with([
        'branch',
        'department',
        'lineManager',
        'employmentHistories.designation',
    ])->findOrFail($id);

    return response()->json([
        'data' => $employee,
    ]);
}


public function update(Request $request, int $id): JsonResponse
{
    $employee = Employee::findOrFail($id);

    $validated = $request->validate([
        'employee_id' => [
            'sometimes', 'required', 'string', 'max:255',
            Rule::unique('employees', 'employee_id')
                ->ignore($employee->id),
        ],

        'first_name' => ['sometimes', 'required', 'string', 'max:255'],
        'last_name' => ['sometimes', 'required', 'string', 'max:255'],

        'email' => [
            'sometimes', 'nullable', 'email', 'max:255',
            Rule::unique('employees', 'email')->ignore($employee->id),
        ],

        'phone' => ['sometimes', 'nullable', 'string', 'max:30'],

        'branch_id' => [
            'sometimes', 'required', 'integer', 'exists:branches,id',
        ],

        'department_id' => ['sometimes', 'required', 'integer'],

        'line_manager_id' => [
            'sometimes', 'nullable', 'integer',
            'exists:employees,id',
            Rule::notIn([$employee->id]),
        ],

        'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],

        'employment_type' => [
            'sometimes', 'required',
            Rule::in(['Probation', 'Permanent', 'Contract', 'Intern']),
        ],

        'probation_end_date' => ['sometimes', 'nullable', 'date'],
        'confirmed_date' => ['sometimes', 'nullable', 'date'],

        // Additional ERD fields
        'name' => ['sometimes', 'nullable', 'string', 'max:255'],
        'nic_number' => ['sometimes', 'nullable', 'string', 'max:50'],
        'date_of_birth' => ['sometimes', 'nullable', 'date'],
        'gender' => ['sometimes', 'nullable', 'string', 'max:30'],
        'contact_number' => ['sometimes', 'nullable', 'string', 'max:30'],
        'personal_email' => ['sometimes', 'nullable', 'email', 'max:255'],
        'residential_address' => ['sometimes', 'nullable', 'string'],
        'emergency_contact_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        'emergency_contact_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
        'branch_code' => ['sometimes', 'nullable', 'string', 'max:255'],
        'joined_date' => ['sometimes', 'nullable', 'date'],
        'status' => ['sometimes', 'nullable', 'string', 'max:50'],
    ]);

    $newBranchId = $validated['branch_id'] ?? $employee->branch_id;
    $newDepartmentId = $validated['department_id'] ?? $employee->department_id;

    $departmentBelongsToBranch = \Illuminate\Support\Facades\DB::table('departments')
        ->where('id', $newDepartmentId)
        ->where('branch_id', $newBranchId)
        ->exists();

    if (! $departmentBelongsToBranch) {
        return response()->json([
            'message' => 'The selected department does not belong to the selected branch.',
        ], 422);
    }

    $employee->update($validated);

    return response()->json([
        'message' => 'Employee updated successfully.',
        'data' => $employee->fresh()->load([
            'branch',
            'department',
            'lineManager',
        ]),
    ]);
}


public function destroy(int $id): JsonResponse
{
    $employee = Employee::findOrFail($id);

    $employee->delete();

    return response()->json([
        'message' => 'Employee soft deleted successfully.',
    ]);
}

}
