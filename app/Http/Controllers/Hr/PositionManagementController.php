<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PositionManagementController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $positions = Designation::withCount('employmentHistories')
            ->withCount(['employmentHistories as current_periods_count' => fn ($query) => $query->whereNull('end_date')])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')->paginate(12)->withQueryString();

        return view('hr.person.position-management', [
            'positions' => $positions, 'search' => $filters['search'] ?? '', 'selectedStatus' => $filters['status'] ?? '',
            'totalPositions' => Designation::count(), 'activePositions' => Designation::where('is_active', true)->count(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->canManageEmploymentStructure(), 403);
        Designation::create($this->validated($request));

        return to_route('hr.person.position-management')->with('status', 'Designation created.');
    }

    public function update(Request $request, Designation $designation)
    {
        abort_unless($request->user()->canManageEmploymentStructure(), 403);
        $data = $this->validated($request, $designation);
        DB::transaction(fn () => Designation::whereKey($designation->id)->lockForUpdate()->firstOrFail()->update($data));

        return to_route('hr.person.position-management')->with('status', 'Designation updated. Existing employment history has been retained.');
    }

    public function destroy(Request $request, Designation $designation)
    {
        abort_unless($request->user()->canManageEmploymentStructure(), 403);
        DB::transaction(function () use ($designation) {
            $locked = Designation::whereKey($designation->id)->lockForUpdate()->firstOrFail();
            if ($locked->employmentHistories()->exists()) {
                throw ValidationException::withMessages([
                    'designation' => 'This designation is used in employment history. Mark it as Inactive instead of deleting it.',
                ]);
            }
            $locked->delete();
        });

        return to_route('hr.person.position-management')->with('status', 'Designation deleted.');
    }

    private function validated(Request $request, ?Designation $designation = null): array
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('designations', 'name')->ignore($designation?->id)],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
