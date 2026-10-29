<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        return response()->json(
            Department::with('branch')
                ->withCount('employees')
                ->paginate(10)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => [
                'required',
                'integer',
                'exists:branches,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $department = Department::create($validated);

        return response()->json([
            'message' => 'Department created successfully.',
            'data' => $department->load('branch'),
        ], 201);
    }

    public function show(string $id)
    {
        $department = Department::with([
            'branch',
            'employees',
        ])->findOrFail($id);

        return response()->json([
            'data' => $department,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'branch_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:branches,id',
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
        ]);

        if (isset($validated['branch_id'])) {
            $newBranchId = $validated['branch_id'];
        } else {
            $newBranchId = $department->branch_id;
        }

        if (isset($validated['name'])) {
            $newName = $validated['name'];
        } else {
            $newName = $department->name;
        }

        $duplicateExists = Department::where(
            'branch_id',
            $newBranchId
        )->where('name', $newName)
         ->where('id', '!=', $department->id)
         ->exists();

        if ($duplicateExists) {
            return response()->json([
                'message' =>
                    'This department name already exists in this branch.',
            ], 422);
        }

        $department->update($validated);

        return response()->json([
            'message' => 'Department updated successfully.',
            'data' => $department->fresh()->load('branch'),
        ]);
    }

    public function destroy(string $id)
    {
        $department = Department::findOrFail($id);

        if ($department->employees()->exists()) {
            return response()->json([
                'message' =>
                    'Cannot delete a department that has employees.',
            ], 409);
        }

        $department->delete();

        return response()->json([
            'message' => 'Department deleted successfully.',
        ]);
    }
}
