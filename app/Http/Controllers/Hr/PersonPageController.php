<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAdditionalField;
use App\Models\DocumentTypeMaster;
use App\Services\EmployeeDocumentService;
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
            'probation_end_date', 'confirmed_date', 'created_at', 'updated_at', 'line_manager_id',
        ]);
        $employee->load(['branch', 'department', 'lineManager', 'employmentHistories.department', 'employmentHistories.designation', 'employmentHistories.manager']);
        $details['probation_end_date'] = $employee->probation_end_date?->toDateString();
        $details['confirmed_date'] = $employee->confirmed_date?->toDateString();
        $details['branch_name'] = $employee->branch?->name;
        $details['department_name'] = $employee->department?->name;
        $details['line_manager_name'] = $employee->lineManager?->name;
        $details['employment_history'] = $employee->employmentHistories->sortByDesc('start_date')->map(fn ($history) => [
            'department' => $history->department?->name,
            'department_id' => $history->department_id, 'branch_id' => $history->department?->branch_id,
            'designation_id' => $history->designation_id, 'manager_id' => $history->manager_id,
            'designation' => $history->designation?->title ?? $history->designation?->name,
            'manager' => $history->manager?->name,
            'start_date' => $history->start_date?->toDateString(),
            'end_date' => $history->end_date?->toDateString(),
            'change_reason' => $history->change_reason,
        ])->values();
        $currentPeriod = $employee->employmentHistories->first(fn ($history) => $history->end_date === null);
        $details['current_designation'] = $currentPeriod?->designation?->name;
        $details['current_designation_id'] = $currentPeriod?->designation_id;
        $details['name'] = $employee->name ?: trim($employee->first_name.' '.$employee->last_name);
        $details['contact_number'] = $employee->contact_number ?? $employee->phone;
        $details['additional_values'] = DB::table('employee_additional_values')->where('employee_id', $employee->id)->pluck('value', 'employee_additional_field_id');
        $details['documents'] = $this->documentDetails($employee);
        if ($request->user()->role === 'admin') {
            $details['resigned_on'] = $employee->resigned_on?->toDateString();
            $details['resignation_reason'] = $employee->resignation_reason;
        }
        $details['onboarding'] = app(EmployeeDocumentService::class)->onboardingStatus($employee);
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
            'additionalFields' => EmployeeAdditionalField::orderBy('name')->get(),
            'additionalValues' => $employee ? DB::table('employee_additional_values')->where('employee_id', $employee->id)->pluck('value', 'employee_additional_field_id') : [],
            'employeeDocuments' => $employee ? $this->documentDetails($employee) : [],
            'documentTypes' => DocumentTypeMaster::orderBy('id')->get(),
            'onboarding' => $employee ? app(EmployeeDocumentService::class)->onboardingStatus($employee) : null,
            'branches' => Branch::query()->orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'designations' => \App\Models\Designation::orderBy('name')->get(),
            'managers' => Employee::where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'Resigned'))->orderBy('first_name')->get(['id', 'employee_id', 'name', 'first_name', 'last_name']),
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
            'section' => ['required', Rule::in(['basic', 'private', 'documents', 'extra'])],
            'record_id' => ['nullable', 'integer'],
        ]);
        $employee = null;
        if (!empty($meta['record_id'])) {
            abort_unless(in_array((int) $meta['record_id'], $request->session()->get('person_registration_ids', []), true), 403);
            $employee = Employee::findOrFail($meta['record_id']);
        }
        if ($meta['section'] === 'extra') {
            abort_unless($employee, 422, 'Save Basic Information first.');
            $fields = EmployeeAdditionalField::all();
            $rules = ['additional_values' => ['nullable', 'array:'.implode(',', $fields->modelKeys())]];
            foreach ($fields as $field) {
                $rules['additional_values.'.$field->id] = match ($field->type) {
                    'number' => ['nullable', 'numeric'],
                    'date' => ['nullable', 'date_format:Y-m-d'],
                    'select' => ['nullable', 'string', Rule::in($field->options ?? [])],
                    default => ['nullable', 'string', 'max:5000'],
                };
            }
            $values = $request->validate($rules)['additional_values'] ?? [];
            DB::transaction(function () use ($employee, $values) {
                foreach ($values as $fieldId => $value) {
                    DB::table('employee_additional_values')->updateOrInsert(
                        ['employee_id' => $employee->id, 'employee_additional_field_id' => $fieldId],
                        ['value' => $value]
                    );
                }
            });
            return response()->json(['id' => $employee->id, 'message' => 'Additional information saved successfully.'])->header('Cache-Control', 'private, no-store');
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
                'probation_end_date' => ['exclude_unless:employment_type,Probation', 'nullable', 'date_format:Y-m-d', 'after_or_equal:joined_date'],
                'confirmed_date' => ['exclude_unless:employment_type,Permanent', Rule::requiredIf($employee?->employment_type === 'Probation' && $request->input('employment_type') === 'Permanent'), 'nullable', 'date_format:Y-m-d', 'after_or_equal:joined_date', 'before_or_equal:'.now('Asia/Colombo')->toDateString()],
                'allow_self_service' => ['required', 'boolean'],
                'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'remove_photo' => ['nullable', 'boolean'],
            ]);
            if ($employee?->status === 'Resigned') $data['allow_self_service'] = false;
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
                    DB::transaction(function () use ($employee, $data) {
                        $locked = Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
                        $current = $locked->employmentHistories()->whereNull('end_date')->first();
                        if ($current && ((int) $data['department_id'] !== (int) $locked->department_id || (int) $data['branch_id'] !== (int) $locked->branch_id)) {
                            throw \Illuminate\Validation\ValidationException::withMessages(['department_id' => 'Use Employment Details → Promotion / Transfer to change the branch or department and retain employment history.']);
                        }
                        $firstStart = $locked->employmentHistories()->min('start_date');
                        if ($firstStart && $data['joined_date'] > substr($firstStart, 0, 10)) {
                            throw \Illuminate\Validation\ValidationException::withMessages(['joined_date' => 'The employment date cannot be after the first employment history period.']);
                        }
                        $locked->update($locked->status === 'Resigned' ? array_merge($data, ['allow_self_service' => false]) : $data);
                    });
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
                    'id' => $file['id'], 'name' => $file['name'], 'expires_on' => $file['expires_on'] ?? null,
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
        $types = DocumentTypeMaster::all()->keyBy('code');
        $keys = $types->keys()->all();
        $rules = [
            'qualifications' => ['nullable', 'array:ol,al,higher_education'],
            'uploads' => ['nullable', 'array:'.implode(',', $keys)],
            'remove_documents' => ['nullable', 'array', 'max:90'],
            'remove_documents.*' => ['required', 'uuid'],
            'upload_expiry' => ['nullable', 'array:'.implode(',', $keys)],
            'document_expiry' => ['nullable', 'array'],
            'document_expiry.*' => ['nullable', 'date_format:Y-m-d'],
        ];
        foreach (['ol', 'al', 'higher_education'] as $key) {
            $rules["qualifications.$key"] = ['nullable', 'array:institution,year,details'];
            $rules["qualifications.$key.institution"] = ['nullable', 'string', 'max:255'];
            $rules["qualifications.$key.year"] = ['nullable', 'integer', 'between:1900,'.date('Y')];
            $rules["qualifications.$key.details"] = ['nullable', 'string', 'max:5000'];
        }
        foreach ($keys as $key) {
            $rules["upload_expiry.$key"] = [Rule::prohibitedIf(!$types[$key]->requires_expiry), Rule::requiredIf($types[$key]->requires_expiry && $request->hasFile("uploads.$key")), 'nullable', 'date_format:Y-m-d'];
            $rules["uploads.$key"] = ['nullable', 'array', 'max:10'];
            $rules["uploads.$key.*"] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'];
        }
        $data = $request->validate($rules);
        $newPaths = [];
        $oldPaths = [];
        $knownFiles = collect($employee->documents ?? [])->flatMap(fn ($category) => $category['files'] ?? [])->pluck('id')->all();
        foreach (array_keys($data['document_expiry'] ?? []) as $fileId) {
            abort_unless(in_array($fileId, $knownFiles, true), 422, 'Unknown employee document.');
        }
        try {
            DB::transaction(function () use ($employee, $request, $data, $keys, $types, &$newPaths, &$oldPaths) {
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
                        else {
                            if (array_key_exists($file['id'], $data['document_expiry'] ?? [])) {
                                if (!$types[$key]->requires_expiry && $data['document_expiry'][$file['id']]) {
                                    throw \Illuminate\Validation\ValidationException::withMessages(['document_expiry.'.$file['id'] => 'This document type does not expire.']);
                                }
                                $file['expires_on'] = $data['document_expiry'][$file['id']];
                                if ($types[$key]->requires_expiry && !$file['expires_on']) {
                                    throw \Illuminate\Validation\ValidationException::withMessages(['document_expiry.'.$file['id'] => 'An expiry date is required for this document type.']);
                                }
                            }
                            $files[] = $file;
                        }
                    }
                    foreach ($request->file("uploads.$key", []) as $upload) {
                        $documentId = (string) Str::uuid();
                        $path = $upload->storeAs((string) $employee->id, $key.'_'.$documentId.'.'.$upload->extension(), 'employee_documents');
                        if (!$path) throw new \RuntimeException('Unable to store document.');
                        $newPaths[] = $path;
                        $files[] = ['disk' => 'employee_documents', 'id' => $documentId, 'path' => $path, 'name' => basename(str_replace('\\', '/', $upload->getClientOriginalName())), 'expires_on' => $data['upload_expiry'][$key] ?? null];
                    }
                    \Illuminate\Support\Facades\Validator::make(['files' => $files], ['files' => ['array', 'max:10']])->validate();
                    $category['files'] = $files;
                    $documents[$key] = $category;
                }
                $locked->documents = $documents;
                $locked->save();
                app(EmployeeDocumentService::class)->sync($locked);
            });
        } catch (\Throwable $error) {
            Storage::disk('employee_documents')->delete($newPaths);
            throw $error;
        }
        foreach ($oldPaths as $oldFile) Storage::disk($oldFile['disk'])->delete($oldFile['path']);
        return response()->json(['id' => $employee->id, 'documents' => $this->documentDetails($employee->fresh()), 'onboarding' => app(EmployeeDocumentService::class)->onboardingStatus($employee->fresh())])
            ->header('Cache-Control', 'private, no-store');
    }

    public function completeOnboarding(Request $request, Employee $employee): JsonResponse
    {
        abort_unless(in_array($employee->id, $request->session()->get('person_registration_ids', []), true), 403);
        $status = DB::transaction(function () use ($employee) {
            $locked = Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $status = app(EmployeeDocumentService::class)->onboardingStatus($locked);
            if (!$status['can_complete']) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'documents' => 'Upload valid mandatory documents: '.implode(', ', $status['blocking_types']),
                ]);
            }
            $locked->forceFill(['onboarding_completed_at' => $locked->onboarding_completed_at ?? now()])->save();
            return app(EmployeeDocumentService::class)->onboardingStatus($locked);
        });
        return response()->json(['onboarding' => $status])->header('Cache-Control', 'private, no-store');
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
