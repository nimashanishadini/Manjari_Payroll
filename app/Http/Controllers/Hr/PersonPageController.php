<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersonPageController extends Controller
{
    public function lookupEmployee(Request $request): JsonResponse
    {
        $data = $request->validate(['employee_id' => ['required', 'string', 'max:255']]);
        $employee = Employee::where('employee_id', $data['employee_id'])->firstOrFail();
        $ids = $request->session()->get('person_registration_ids', []);
        $request->session()->put('person_registration_ids', array_values(array_unique([...$ids, $employee->id])));
        $details = $employee->only([
            'id', 'employee_id', 'name', 'branch_id', 'department_id', 'joined_date',
            'employment_type', 'allow_self_service', 'nic_number', 'date_of_birth', 'gender',
            'contact_number', 'personal_email', 'residential_address',
            'emergency_contact_name', 'emergency_contact_phone', 'blood_group',
            'first_name', 'last_name', 'email', 'phone', 'branch_code', 'status',
            'probation_end_date', 'confirmed_date', 'created_at', 'updated_at',
        ]);
        $employee->load(['branch', 'department', 'lineManager', 'employmentHistories.department', 'employmentHistories.designation', 'employmentHistories.manager']);
        $details['branch_name'] = $employee->branch?->name;
        $details['department_name'] = $employee->department?->name;
        $details['line_manager_name'] = $employee->lineManager?->name;
        $details['employment_history'] = $employee->employmentHistories->sortByDesc('start_date')->map(fn ($history) => [
            'department' => $history->department?->name,
            'designation' => $history->designation?->title ?? $history->designation?->name,
            'manager' => $history->manager?->name,
            'start_date' => $history->start_date?->toDateString(),
            'end_date' => $history->end_date?->toDateString(),
            'change_reason' => $history->change_reason,
        ])->values();
        $details['name'] = $employee->name ?: trim($employee->first_name.' '.$employee->last_name);
        $details['contact_number'] = $employee->contact_number ?? $employee->phone;
        $details['documents'] = $this->documentDetails($employee);
        $details['photo_url'] = $employee->profile_photo ? route('hr.person.registration.photo', $employee) : null;
        return response()->json(['employee' => $details])->header('Cache-Control', 'private, no-store');
    }

    public function create(Request $request): \Illuminate\Http\Response
    {
        $employee = null;
        if ($request->filled('employee')) {
            abort_unless(in_array((int) $request->input('employee'), $request->session()->get('person_registration_ids', []), true), 403);
            $employee = Employee::findOrFail($request->input('employee'));
        }
        return response()->view('hr.person.add-employee', [
            'employee' => $employee,
            'employeeDocuments' => $employee ? $this->documentDetails($employee) : [],
            'branches' => Branch::query()->orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'designations' => \App\Models\Designation::where('is_active', true)->orderBy('name')->get(),
            'managers' => Employee::orderBy('name')->get(['id', 'employee_id', 'name']),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function bankDetails(Request $request, Employee $employee): JsonResponse
    {
        abort_unless($request->user()->canManageEmployeeBankDetails(), 403);
        $request->validate(['bank_password' => ['required', 'current_password:web']]);
        \Illuminate\Support\Facades\Log::info('Employee bank details viewed', ['user_id' => $request->user()->id, 'employee_id' => $employee->id]);
        return response()->json(['bank' => $employee->only([
            'bank_name', 'bank_branch', 'bank_account_name', 'bank_account_number',
        ])])->header('Cache-Control', 'private, no-store');
    }

    public function registrationPhoto(Request $request, Employee $employee)
    {
        abort_unless(in_array($employee->id, $request->session()->get('person_registration_ids', []), true), 403);
        return $this->directoryPhoto($employee);
    }

    public function directoryPhoto(Employee $employee)
    {
        abort_unless($employee->profile_photo, 404);
        $path = basename($employee->profile_photo);
        if (Storage::disk('employee_photos')->exists($path)) {
            return Storage::disk('employee_photos')->response($path, null, ['Cache-Control' => 'private, no-store']);
        }
        abort_unless(Storage::disk('local')->exists($employee->profile_photo), 404);
        return Storage::disk('local')->response($employee->profile_photo, null, ['Cache-Control' => 'private, no-store']);
    }

    public function saveSection(Request $request): JsonResponse
    {
        $meta = $request->validate([
            'section' => ['required', Rule::in(['basic', 'private', 'documents'])],
            'record_id' => ['nullable', 'integer'],
        ]);
        $employee = null;
        if (!empty($meta['record_id'])) {
            abort_unless(in_array((int) $meta['record_id'], $request->session()->get('person_registration_ids', []), true), 403);
            $employee = Employee::findOrFail($meta['record_id']);
        }
        if ($meta['section'] === 'documents') {
            abort_unless($employee, 422, 'Save Basic Information first.');
            return $this->saveDocuments($request, $employee);
        }
        if ($meta['section'] === 'private') {
            abort_unless($employee, 422, 'Save Basic Information first.');
            $bankFields = ['bank_name', 'bank_branch', 'bank_account_name', 'bank_account_number'];
            if ($request->hasAny([...$bankFields, 'update_bank', 'bank_password'])) {
                abort_unless($request->user()->canManageEmployeeBankDetails(), 403);
            }
            $bank = [];
            $request->validate(['update_bank' => ['nullable', 'boolean']]);
            if ($request->boolean('update_bank')) {
                $bank = $request->validate([
                    'bank_password' => ['required', 'current_password:web'],
                    'bank_name' => ['required', 'string', 'max:150'],
                    'bank_branch' => ['required', 'string', 'max:150'],
                    'bank_account_name' => ['required', 'string', 'max:255'],
                    'bank_account_number' => ['required', 'string', 'regex:/^[0-9]{4,34}$/'],
                ]);
                unset($bank['bank_password']);
            }
            $data = $request->validate([
                'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
                'nic_number' => ['nullable', 'string', 'max:50'],
                'date_of_birth' => ['nullable', 'date', 'before:today'],
                'gender' => ['nullable', Rule::in(['Male', 'Female', 'Other'])],
                'contact_number' => ['nullable', 'string', 'max:30'],
                'personal_email' => ['nullable', 'email', 'max:255'],
                'residential_address' => ['nullable', 'string', 'max:5000'],
                'emergency_contact_name' => ['nullable', 'string', 'max:255'],
                'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            ]);
            $employee->fill(array_merge($data, ['phone' => $data['contact_number'] ?? null]));
            foreach ($bank as $field => $value) $employee->{$field} = $value;
            $employee->save();
            if ($bank) \Illuminate\Support\Facades\Log::info('Employee bank details updated', ['user_id' => $request->user()->id, 'employee_id' => $employee->id]);
        } else {
            $data = $request->validate([
                'employee_id' => ['required', 'string', 'max:255', Rule::unique('employees', 'employee_id')->ignore($employee?->id)],
                'name' => ['required', 'string', 'max:255'],
                'branch_id' => ['required', 'integer', 'exists:branches,id'],
                'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('branch_id', $request->input('branch_id'))],
                'joined_date' => ['required', 'date'],
                'employment_type' => ['required', Rule::in(['Probation', 'Permanent', 'Contract', 'Intern'])],
                'allow_self_service' => ['required', 'boolean'],
                'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'remove_photo' => ['nullable', 'boolean'],
            ]);
            $name = preg_split('/\s+/', trim($data['name']), 2);
            $data['first_name'] = $name[0];
            $data['last_name'] = $name[1] ?? '';
            $data['branch_code'] = Branch::findOrFail($data['branch_id'])->branch_code;
            $oldPhoto = $employee?->profile_photo;
            unset($data['profile_photo'], $data['remove_photo']);
            $newPhoto = null;
            if ($request->hasFile('profile_photo')) {
                $photo = $request->file('profile_photo');
                $newPhoto = $photo->storeAs('', Str::uuid().'.'.$photo->extension(), 'employee_photos');
            }
            if ($newPhoto || $request->boolean('remove_photo')) {
                $data['profile_photo'] = $newPhoto;
            }
            try {
                if ($employee) {
                    $employee->update($data);
                } else {
                    $employee = Employee::create(array_merge($data, ['status' => 'Active']));
                    $ids = $request->session()->get('person_registration_ids', []);
                    $ids[] = $employee->id;
                    $request->session()->put('person_registration_ids', $ids);
                }
            } catch (\Throwable $error) {
                if ($newPhoto) Storage::disk('employee_photos')->delete($newPhoto);
                throw $error;
            }
            if ($oldPhoto && ($newPhoto || $request->boolean('remove_photo'))) {
                Storage::disk('employee_photos')->delete(basename($oldPhoto));
                Storage::disk('local')->delete($oldPhoto);
            }
        }
        return response()->json(['id' => $employee->id, 'message' => 'Section saved successfully.'])->header('Cache-Control', 'private, no-store');
    }

    private function documentDetails(Employee $employee): array
    {
        $documents = $employee->documents ?? [];
        foreach ($documents as &$category) {
            foreach ($category['files'] ?? [] as $index => $file) {
                $category['files'][$index] = [
                    'id' => $file['id'], 'name' => $file['name'],
                    'url' => route('hr.person.documents.download', [$employee, $file['id']]),
                ];
            }
        }
        return $documents;
    }

    public function downloadDocument(Request $request, Employee $employee, string $document)
    {
        abort_unless(in_array($employee->id, $request->session()->get('person_registration_ids', []), true), 403);
        foreach ($employee->documents ?? [] as $category) {
            foreach ($category['files'] ?? [] as $file) {
                if ($file['id'] === $document) {
                    abort_unless(Storage::disk($file['disk'] ?? 'local')->exists($file['path']), 404);
                    return Storage::disk($file['disk'] ?? 'local')->download($file['path'], $file['name'], ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
                }
            }
        }
        abort(404);
    }

    private function saveDocuments(Request $request, Employee $employee): JsonResponse
    {
        $keys = array_keys(config('employee_documents'));
        $rules = [
            'qualifications' => ['nullable', 'array:ol,al,higher_education'],
            'uploads' => ['nullable', 'array:'.implode(',', $keys)],
            'remove_documents' => ['nullable', 'array', 'max:90'],
            'remove_documents.*' => ['required', 'uuid'],
        ];
        foreach (['ol', 'al', 'higher_education'] as $key) {
            $rules["qualifications.$key"] = ['nullable', 'array:institution,year,details'];
            $rules["qualifications.$key.institution"] = ['nullable', 'string', 'max:255'];
            $rules["qualifications.$key.year"] = ['nullable', 'integer', 'between:1900,'.date('Y')];
            $rules["qualifications.$key.details"] = ['nullable', 'string', 'max:5000'];
        }
        foreach ($keys as $key) {
            $rules["uploads.$key"] = ['nullable', 'array', 'max:10'];
            $rules["uploads.$key.*"] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'];
        }
        $data = $request->validate($rules);
        $newPaths = [];
        $oldPaths = [];
        try {
            DB::transaction(function () use ($employee, $request, $data, $keys, &$newPaths, &$oldPaths) {
                $locked = Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
                $documents = $locked->documents ?? [];
                foreach ($keys as $key) {
                    $category = $documents[$key] ?? [];
                    if (isset($data['qualifications'][$key])) {
                        $category['qualification'] = $data['qualifications'][$key];
                    }
                    $files = [];
                    foreach ($category['files'] ?? [] as $file) {
                        if (in_array($file['id'], $data['remove_documents'] ?? [], true)) $oldPaths[] = ['disk' => $file['disk'] ?? 'local', 'path' => $file['path']];
                        else $files[] = $file;
                    }
                    foreach ($request->file("uploads.$key", []) as $upload) {
                        $documentId = (string) Str::uuid();
                        $path = $upload->storeAs((string) $employee->id, $key.'_'.$documentId.'.'.$upload->extension(), 'employee_documents');
                        if (!$path) throw new \RuntimeException('Unable to store document.');
                        $newPaths[] = $path;
                        $files[] = ['disk' => 'employee_documents', 'id' => $documentId, 'path' => $path, 'name' => basename(str_replace('\\', '/', $upload->getClientOriginalName()))];
                    }
                    \Illuminate\Support\Facades\Validator::make(['files' => $files], ['files' => ['array', 'max:10']])->validate();
                    $category['files'] = $files;
                    $documents[$key] = $category;
                }
                $locked->documents = $documents;
                $locked->save();
            });
        } catch (\Throwable $error) {
            Storage::disk('employee_documents')->delete($newPaths);
            throw $error;
        }
        foreach ($oldPaths as $oldFile) Storage::disk($oldFile['disk'])->delete($oldFile['path']);
        return response()->json(['id' => $employee->id, 'documents' => $this->documentDetails($employee->fresh())])
            ->header('Cache-Control', 'private, no-store');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $branches = Branch::query()
            ->with([
                'departments' => fn ($query) =>
                    $query->withCount('employees')->orderBy('name'),
            ])
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        $query = Employee::query()
            ->with(['branch', 'department']);

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['department_id'])) {
            $department = Department::findOrFail(
                $filters['department_id']
            );

            if (
                !empty($filters['branch_id']) &&
                $department->branch_id != $filters['branch_id']
            ) {
                abort(422, 'Department does not belong to this branch.');
            }

            $query->where('department_id', $department->id);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('employee_id', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return view('hr.person.person', [
            'branches' => $branches,
            'employees' => $query
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString(),
            'selectedBranch' => $filters['branch_id'] ?? null,
            'selectedDepartment' => $filters['department_id'] ?? null,
            'search' => $filters['search'] ?? '',
            'totalEmployees' => Employee::count(),
        ]);
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $exists = Department::query()
            ->where('branch_id', $validated['branch_id'])
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return back()
                ->withErrors([
                    'name' => 'This department already exists in the branch.',
                ])
                ->withInput()
                ->with('open_modal', 'department');
        }

        Department::create($validated);

        return redirect()
            ->route('hr.person.index', [
                'branch_id' => $validated['branch_id'],
            ])
            ->with('success', 'Department created successfully.');
    }

    public function storeEmployee(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => [
                'required', 'string', 'max:255',
                Rule::unique('employees', 'employee_id'),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')
                    ->where('branch_id', $request->input('branch_id')),
            ],
            'employment_type' => [
                'required',
                Rule::in(['Probation', 'Permanent', 'Contract', 'Intern']),
            ],
            'joined_date' => ['required', 'date'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:30'],
            'nic_number' => ['nullable', 'string', 'max:50'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'residential_address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
        ]);

        DB::transaction(function () use ($validated) {
            $branch = Branch::findOrFail($validated['branch_id']);

            Employee::create(array_merge($validated, [
                'name' => trim(
                    $validated['first_name'].' '.$validated['last_name']
                ),
                'branch_code' => $branch->branch_code,
                'phone' => $validated['contact_number'] ?? null,
                'status' => 'Active',
            ]));
        });

        return redirect()
            ->route('hr.person.index', [
                'branch_id' => $validated['branch_id'],
            ])
            ->with('success', 'Employee created successfully.');
    }
}
