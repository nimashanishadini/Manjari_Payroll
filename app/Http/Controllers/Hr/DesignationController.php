<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DesignationController extends Controller
{
    public function index()
    {
        return response()->json(
            Designation::withCount('employmentHistories')->paginate(10)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:designations,name'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $designation = Designation::create($validated);

        return response()->json([
            'message' => 'Designation created successfully.',
            'data' => $designation,
        ], 201);
    }

    public function show(string $id)
    {
        $designation = Designation::withCount('employmentHistories')
            ->findOrFail($id);

        return response()->json(['data' => $designation]);
    }

    public function update(Request $request, string $id)
    {
        $designation = Designation::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('designations', 'name')->ignore($designation->id),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $designation->update($validated);

        return response()->json([
            'message' => 'Designation updated successfully.',
            'data' => $designation->fresh(),
        ]);
    }

    public function destroy(string $id)
    {
        $designation = Designation::findOrFail($id);

        if ($designation->employmentHistories()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a designation used in employment history.',
            ], 409);
        }

        $designation->delete();

        return response()->json([
            'message' => 'Designation deleted successfully.',
        ]);
    }
}
