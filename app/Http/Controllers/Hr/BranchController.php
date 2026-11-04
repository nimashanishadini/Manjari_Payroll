<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeManagement($request);
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($data['search'] ?? '');
        $branches = Branch::withCount(['departments', 'employees', 'biometricDevices'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('branch_code', 'like', "%{$search}%")))
            ->orderBy('name')->paginate(15)->withQueryString();
        return view('hr.person.branches', ['branches' => $branches, 'search' => $search,
            'totalBranches' => Branch::count(), 'timezones' => \DateTimeZone::listIdentifiers()]);
    }

    public function store(Request $request)
    {
        $this->authorizeManagement($request);
        Branch::create($request->validate([
            'branch_code' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:branches,branch_code'],
            'name' => ['required', 'string', 'max:255'], 'timezone' => ['required', 'timezone'],
        ]));
        return to_route('hr.organization-structure.index')->with('status', 'Branch created successfully.');
    }

    public function update(Request $request, Branch $branch)
    {
        $this->authorizeManagement($request);
        $data = $request->validate([
            // Codes are permanent identifiers used in raw attendance and historical snapshots.
            'branch_code' => ['sometimes', 'required', Rule::in([$branch->branch_code])],
            'name' => ['required', 'string', 'max:255'], 'timezone' => ['required', 'timezone'],
        ]);
        $branch->update(collect($data)->only(['name', 'timezone'])->all());
        return to_route('hr.organization-structure.index')->with('status', 'Branch updated successfully.');
    }

    public function destroy(Request $request, Branch $branch)
    {
        $this->authorizeManagement($request);
        try {
            DB::transaction(function () use ($branch) {
                $locked = Branch::whereKey($branch->id)->lockForUpdate()->firstOrFail();
                if ($locked->departments()->exists() || $locked->biometricDevices()->exists()
                    || Employee::withTrashed()->where('branch_id', $locked->id)->orWhere('branch_code', $locked->branch_code)->exists()
                    || DB::table('biometric_staging_users')->where('branch_id', $locked->id)->exists()
                    || DB::table('raw_attendance')->where('branch_code', $locked->branch_code)->exists()) {
                    throw ValidationException::withMessages(['branch' => 'This branch is in use. Its departments, employees, devices and history must be retained.']);
                }
                $locked->delete();
            });
        } catch (QueryException $error) {
            if (! in_array((string) $error->getCode(), ['23000', '23503'], true)) throw $error;
            throw ValidationException::withMessages(['branch' => 'This branch is referenced by other records and cannot be deleted.']);
        }
        return to_route('hr.organization-structure.index')->with('status', 'Unused branch deleted.');
    }

    private function authorizeManagement(Request $request): void
    {
        abort_unless($request->user()?->canManageEmploymentStructure(), 403);
    }
}
