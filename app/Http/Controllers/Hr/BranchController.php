<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index()
    {
        return response()->json(
            Branch::withCount(['departments', 'employees'])
                ->paginate(10)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_code' => [
                'required',
                'string',
                'max:255',
                'unique:branches,branch_code',
            ],
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'timezone'],
        ]);

        $branch = Branch::create($validated);

        return response()->json([
            'message' => 'Branch created successfully.',
            'data' => $branch,
        ], 201);
    }

    public function show(string $id)
    {
        $branch = Branch::with([
            'departments',
            'employees',
        ])->findOrFail($id);

        return response()->json([
            'data' => $branch,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'branch_code' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('branches', 'branch_code')
                    ->ignore($branch->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'timezone' => ['sometimes', 'required', 'timezone'],
        ]);

        $branch->update($validated);

        return response()->json([
            'message' => 'Branch updated successfully.',
            'data' => $branch->fresh(),
        ]);
    }

    public function destroy(string $id)
    {
        $branch = Branch::findOrFail($id);

        if (
            $branch->departments()->exists() ||
            $branch->employees()->exists()
        ) {
            return response()->json([
                'message' =>
                    'Cannot delete a branch that has departments or employees.',
            ], 409);
        }

        $branch->delete();

        return response()->json([
            'message' => 'Branch deleted successfully.',
        ]);
    }
}
