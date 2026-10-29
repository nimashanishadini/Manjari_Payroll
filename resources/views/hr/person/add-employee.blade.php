
<x-layouts.app title="Add Person" section="person">

<div class="flex min-h-[calc(100dvh-54px)] min-w-0 flex-col bg-slate-50">

    {{-- PAGE HEADER --}}
    <div class="border-b border-slate-200 bg-white px-5 py-5 sm:px-8">

        <div class="flex items-center gap-4">

            <a
                href="{{ route('hr.person.index') }}"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-300 text-slate-600 transition hover:border-rose-500 hover:bg-rose-50 hover:text-rose-600"
                title="Back to Person Management"
            >
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="20" height="20" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round">
                    <path d="m12 19-7-7 7-7"/>
                    <path d="M19 12H5"/>
                </svg>
            </a>

            <div>
                <h1 class="text-xl font-bold text-slate-900">
                    Add Person
                </h1>
                <p class="mt-1 text-xs text-slate-500">
                    Register a new employee
                </p>
            </div>

        </div>

    </div>

    {{-- TABS --}}
    <div class="border-b border-slate-200 bg-white">

        <div
            class="flex gap-1 overflow-x-auto px-4 sm:px-7"
            role="tablist"
            aria-label="Employee registration sections"
            id="employeeTabs"
        >

            @php
                $tabs = [
                    ['id' => 'basic', 'label' => 'Basic Information'],
                    ['id' => 'private', 'label' => 'Private Information'],
                    ['id' => 'access', 'label' => 'Access Level'],
                    ['id' => 'schedule', 'label' => 'Schedule'],
                    ['id' => 'documents', 'label' => 'Documents & Qualifications'],
                    ['id' => 'employment', 'label' => 'Employment Details'],
                ];
            @endphp

            @foreach($tabs as $tab)
                <button
                    type="button"
                    id="tab-{{ $tab['id'] }}"
                    role="tab"
                    aria-controls="panel-{{ $tab['id'] }}"
                    aria-selected="{{ $tab['id'] === 'basic' ? 'true' : 'false' }}"
                    onclick="showEmployeeTab('{{ $tab['id'] }}')"
                    data-employee-tab="{{ $tab['id'] }}"
                    class="shrink-0 whitespace-nowrap border-b-[3px] px-4 py-4 text-sm font-medium transition
                    {{ $tab['id'] === 'basic'
                        ? 'border-rose-600 text-rose-600'
                        : 'border-transparent text-slate-500 hover:text-slate-800' }}"
                >
                    {{ $tab['label'] }}
                </button>
            @endforeach

        </div>

    </div>

    {{-- CONTENT --}}
    <div class="min-w-0 flex-1 px-4 py-6 sm:px-8">

        <div class="mx-auto w-full max-w-6xl">

            {{-- BASIC INFORMATION --}}
            <section
                id="panel-basic"
                role="tabpanel"
                aria-labelledby="tab-basic"
                data-employee-panel="basic"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8"
            >

                <div class="mb-7 border-b border-slate-100 pb-5">
                    <h2 class="text-lg font-bold text-slate-900">
                        Basic Information
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Enter the employee's basic registration details.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_190px]">

                    {{-- FORM FIELDS --}}
                    <div class="min-w-0 space-y-5">

                        {{-- EMPLOYEE ID --}}
                        <div class="grid gap-2 sm:grid-cols-[160px_minmax(0,1fr)] sm:items-start">

                            <label for="employee_id"
                                   class="pt-2 text-sm font-medium text-slate-700">
                                Employee ID <span class="text-red-500">*</span>
                            </label>

                            <div>
                                <input
                                    type="text"
                                    id="employee_id"
                                    name="employee_id"
                                    value="{{ old('employee_id') }}"
                                    required
                                    placeholder="Enter employee ID"
                                    maxlength="255"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-rose-500 focus:ring-2 focus:ring-rose-100"
                                >

                                <p class="mt-2 text-xs leading-5 text-slate-500">
                                    This ID will be used for biometric device enrollment.
                                    Confirm the ID before saving.
                                </p>
                                <p id="employeeLookupStatus" role="status" aria-live="polite" class="mt-2 text-xs text-slate-600"></p>
                            </div>

                        </div>

                        {{-- BRANCH --}}
                        <div class="grid gap-2 sm:grid-cols-[160px_minmax(0,1fr)] sm:items-center">

                            <label for="employee_branch"
                                   class="text-sm font-medium text-slate-700">
                                Branch <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="employee_branch"
                                name="branch_id"
                                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-100"
                            >
                                <option value="">Select branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>

                        </div>

                        {{-- DEPARTMENT --}}
                        <div class="grid gap-2 sm:grid-cols-[160px_minmax(0,1fr)] sm:items-center">

                            <label for="employee_department"
                                   class="text-sm font-medium text-slate-700">
                                Department <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="employee_department"
                                name="department_id"
                                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-100"
                            >
                                <option value="">Select department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" data-branch-id="{{ $department->branch_id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>

                        </div>

                        {{-- NAME --}}
                        <div class="grid gap-2 sm:grid-cols-[160px_minmax(0,1fr)] sm:items-center">

                            <label for="employee_name"
                                   class="text-sm font-medium text-slate-700">
                                Full Name <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                id="employee_name"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                maxlength="255"
                                placeholder="Enter full name"
                                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-100"
                            >

                        </div>

                        {{-- JOINED DATE --}}
                        <div class="grid gap-2 sm:grid-cols-[160px_minmax(0,1fr)] sm:items-center">

                            <label for="joined_date"
                                   class="text-sm font-medium text-slate-700">
                                Date of Employment <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="date"
                                id="joined_date"
                                name="joined_date"
                                value="{{ old('joined_date', now()->toDateString()) }}"
                                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-100"
                            >

                        </div>

                        <div class="grid gap-2 sm:grid-cols-[160px_minmax(0,1fr)] sm:items-center">
                            <label for="employment_type" class="text-sm font-medium text-slate-700">Employment Type *</label>
                            <select id="employment_type" name="employment_type" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                                <option value="">Select employment type</option>
                                @foreach(['Probation', 'Permanent', 'Contract', 'Intern'] as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- SELF SERVICE --}}
                        <div class="grid gap-2 sm:grid-cols-[160px_minmax(0,1fr)] sm:items-center">

                            <label for="self_service"
                                   class="text-sm font-medium text-slate-700">
                                Allow Login to Self-Service
                            </label>

                            <label class="inline-flex cursor-pointer items-center gap-3">

                                <input
                                    type="checkbox"
                                    id="self_service"
                                    name="allow_self_service"
                                    value="1"
                                    class="h-4 w-4 accent-rose-600"
                                >

                                <span class="text-sm text-slate-600">
                                    Enable employee self-service
                                </span>

                            </label>

                        </div>

                    </div>

                    {{-- PROFILE IMAGE --}}
                    <div class="flex flex-col items-center gap-4">

                        <div class="flex h-48 w-40 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-slate-300 bg-slate-100">

                            <img id="employeePhotoPreview" alt="Selected employee profile photo" hidden style="width:100%;height:100%;object-fit:cover;">
                            <svg
                                id="employeePhotoPlaceholder"
                                xmlns="http://www.w3.org/2000/svg"
                                width="100"
                                height="100"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="#94a3b8"
                                stroke-width="1"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <circle cx="12" cy="8" r="4"/>
                                <path d="M4 21a8 8 0 0 1 16 0"/>
                            </svg>

                        </div>

                        <input type="file" id="employeePhoto" name="profile_photo" accept="image/jpeg,image/png,image/webp" hidden aria-describedby="employeePhotoHelp employeePhotoError">

                        <button
                            type="button"
                            onclick="document.getElementById('employeePhoto').click()"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700"
                        >
                            Profile Photo
                        </button>

                        <button type="button" id="removeEmployeePhoto" onclick="clearEmployeePhoto()" hidden class="text-xs font-semibold text-rose-600">Remove Photo</button>

                        <p id="employeePhotoHelp" class="text-center text-xs text-slate-400">
                            JPG, PNG or WebP. Maximum 5MB.
                        </p>
                        <p id="employeePhotoError" role="alert" class="text-center text-xs text-red-600"></p>

                    </div>

                </div>

            </section>

            {{-- REMAINING TABS --}}
            @foreach(array_slice($tabs, 1) as $tab)
                <section
                    id="panel-{{ $tab['id'] }}"
                    role="tabpanel"
                    aria-labelledby="tab-{{ $tab['id'] }}"
                    data-employee-panel="{{ $tab['id'] }}"
                    hidden
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"
                >

                    <h2 class="text-lg font-bold text-slate-900">
                        {{ $tab['label'] }}
                    </h2>

                    @if($tab['id'] === 'private')
                        @include('hr.person.partials.private-information')
                    @elseif($tab['id'] === 'employment')
                        @include('hr.person.partials.employment-details')
                    @elseif($tab['id'] === 'documents')
                        @include('hr.person.partials.documents-qualifications')
                    @else
                        <p class="mt-3 text-sm text-slate-500">This section will be configured next.</p>
                    @endif

                </section>
            @endforeach

        </div>

    </div>

    {{-- FOOTER --}}
    
{{-- FOOTER --}}
<div class="sticky bottom-0 z-20 border-t border-slate-200 bg-white px-5 py-4 shadow-[0_-4px_20px_rgba(15,23,42,0.06)] sm:px-8">

    <div class="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-3">

        {{-- LEFT SIDE --}}
        <div class="flex items-center gap-3">
            <span id="employeeStepText"
                  class="text-xs font-semibold text-slate-500 sm:text-sm">
                Step 1 of {{ count($tabs) }}
            </span>

            <div class="hidden h-1.5 w-28 overflow-hidden rounded-full bg-slate-200 sm:block">
                <div id="employeeStepProgress"
                     class="h-full rounded-full bg-gradient-to-r from-rose-500 to-red-600 transition-all duration-300"
                     style="width:{{ 100 / count($tabs) }}%">
                </div>
            </div>
        </div>

        <p id="employeeSaveStatus" role="status" aria-live="polite" class="text-sm text-slate-600" style="white-space:pre-line"></p>
        {{-- RIGHT SIDE --}}
        <div class="flex flex-wrap items-center justify-end gap-2 sm:gap-3">

            <a href="{{ route('hr.person.index') }}"
               class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 sm:px-5">
                Cancel
            </a>

            <button
                id="previousEmployeeButton"
                type="button"
                onclick="previousEmployeeTab()"
                class="hidden min-h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:px-5"
            >
                Previous
            </button>

            <button
                id="saveEmployeeButton"
                type="button"
                onclick="saveEmployee()"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-green-600 px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-emerald-500/20 transition hover:from-emerald-600 hover:to-green-700 sm:px-7"
            >
                Save Basic Information
            </button>

        </div>
    </div>
</div>


</div>

<script>

const employeeTabIds = @json(array_column($tabs, 'id'));

let currentEmployeeTab = 'basic';
const savedEmployee = @json($employee);
let employeeRecordId = savedEmployee?.id ?? null;
let photoRemoved = false;
let employeeSaving = false;
let loadedEmployeeId = savedEmployee?.employee_id ?? null;
let employeeLookupTimer;
let employeeLookupRequest = null;
let bankRequestVersion = 0;
let employmentLookupVersion = 0;
let employmentLookupTimer;
let historyEmployee = null;
let historySaving = false;

function populateEmployeeDocuments(documents) {
    document.querySelectorAll('[data-document-category]').forEach(card => {
        const category = documents[card.dataset.documentCategory] ?? {};
        card.querySelectorAll('[data-qualification]').forEach(input => {
            input.value = category.qualification?.[input.dataset.qualification] ?? '';
        });
        const files = category.files ?? [];
        card.querySelector('[data-document-count]').textContent = files.length ? `${files.length} file${files.length === 1 ? '' : 's'} saved` : 'No files saved';
        const list = card.querySelector('[data-saved-documents]');
        list.replaceChildren();
        files.forEach(file => {
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-xs';
            const link = document.createElement('a');
            link.href = file.url;
            link.textContent = file.name;
            link.className = 'min-w-0 break-all font-medium text-slate-700 underline hover:text-rose-600';
            const label = document.createElement('label');
            label.className = 'flex shrink-0 items-center gap-1.5 text-rose-600';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'remove_documents[]';
            checkbox.value = file.id;
            label.append(checkbox, document.createTextNode('Remove'));
            row.append(link, label);
            list.append(row);
        });
        card.querySelector('input[type="file"]').value = '';
        card.querySelector('[data-file-selection]').textContent = '';
    });
}

async function viewEmploymentDetails() {
    historyEmployee = null;
    clearTimeout(employmentLookupTimer);
    const id = document.getElementById('employmentLookupId').value.trim();
    const version = ++employmentLookupVersion;
    const status = document.getElementById('employmentDetailsStatus');
    const result = document.getElementById('employmentDetailsResult');
    result.hidden = true;
    result.querySelectorAll('[data-employment-detail]').forEach(field => field.textContent = '');
    document.getElementById('employmentHistoryResult').replaceChildren();
    if (!id) {
        status.textContent = 'Enter an Employee ID.';
        return;
    }
    status.textContent = 'Loading employee details…';
    try {
        const url = new URL(@json(route('hr.person.lookup')));
        url.searchParams.set('employee_id', id);
        const response = await fetch(url, {headers: {'Accept': 'application/json'}, cache: 'no-store'});
        if (version !== employmentLookupVersion) return;
        if (response.status === 404) {
            status.textContent = 'No employee found for this ID.';
            return;
        }
        if (!response.ok) throw new Error('Unable to load');
        const {employee} = await response.json();
        if (version !== employmentLookupVersion) return;
        historyEmployee = employee;
        document.getElementById('employmentHistoryForm').reset();
        document.getElementById('historyEnd').disabled = false;
        document.getElementById('historyEnd').min = '';
        filterHistoryDepartments();
        document.getElementById('historySaveStatus').textContent = '';
        document.getElementById('historyStart').min = employee.joined_date?.slice(0, 10) ?? '';
        document.querySelectorAll('#historyManager option[value]').forEach(option => {
            option.disabled = option.value === String(employee.id);
        });
        const photo = document.getElementById('employmentDetailsPhoto');
        photo.hidden = !employee.photo_url;
        if (employee.photo_url) photo.src = employee.photo_url;
        else photo.removeAttribute('src');
        result.querySelectorAll('[data-employment-detail]').forEach(field => {
            const key = field.dataset.employmentDetail;
            const value = employee[key];
            field.textContent = key === 'allow_self_service' ? (Number(value) ? 'Enabled' : 'Disabled') : (value ?? '—');
        });
        const history = document.getElementById('employmentHistoryResult');
        if (!employee.employment_history?.length) history.textContent = 'No employment history recorded.';
        for (const item of employee.employment_history ?? []) {
            const row = document.createElement('p');
            row.textContent = `${item.start_date ?? '—'} – ${item.end_date ?? 'Present'} | ${item.department ?? '—'} | ${item.designation ?? '—'} | Manager: ${item.manager ?? '—'} | ${item.change_reason ?? ''}`;
            history.appendChild(row);
        }
        status.textContent = 'Employee details loaded.';
        result.hidden = false;
    } catch {
        if (version === employmentLookupVersion) status.textContent = 'Unable to load employee details. Please try again.';
    }
}

function filterHistoryDepartments() {
    const branch = document.getElementById('historyBranch').value;
    const department = document.getElementById('historyDepartment');
    Array.from(department.options).forEach(option => {
        if (!option.value) return;
        option.hidden = option.dataset.branch !== branch;
        option.disabled = option.hidden;
    });
    if (department.selectedOptions[0]?.disabled) department.value = '';
}

async function saveEmploymentPeriod(event) {
    event.preventDefault();
    if (!historyEmployee || historySaving) return;
    const employee = historyEmployee;
    const form = event.currentTarget;
    const status = document.getElementById('historySaveStatus');
    const button = document.getElementById('saveHistoryButton');
    const transfer = document.getElementById('historyAction').value === 'transfer';
    const template = transfer ? @json(route('hr.person.history.transfer', 0)) : @json(route('hr.person.history.store', 0));
    const data = new FormData(form);
    if (transfer || !data.get('end_date')) data.delete('end_date');
    status.textContent = 'Saving employment period…';
    historySaving = true;
    button.disabled = true;
    document.getElementById('employmentLookupId').disabled = true;
    try {
        const response = await fetch(template.replace('/0/history', '/' + employee.id + '/history'), {
            method: 'POST', body: data,
            headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
        });
        const result = await response.json();
        if (!response.ok) {
            status.textContent = result.errors ? Object.values(result.errors).flat().join('\n') : (result.message || 'Unable to save employment period.');
            return;
        }
        await viewEmploymentDetails();
        document.getElementById('historySaveStatus').textContent = 'Employment period saved successfully.';
    } catch {
        status.textContent = 'Unable to save. Check your connection and try again.';
    } finally {
        historySaving = false;
        button.disabled = false;
        document.getElementById('employmentLookupId').disabled = false;
    }
}

function clearBankFields() {
    bankRequestVersion++;
    for (const id of ['bank_name', 'bank_branch', 'bank_account_name', 'bank_account_number', 'bank_password']) {
        const input = document.getElementById(id);
        if (input) input.value = '';
    }
    const update = document.getElementById('update_bank');
    if (update) update.checked = false;
    const status = document.getElementById('bankDetailsStatus');
    if (status) status.textContent = '';
}

async function loadEmployeeBankDetails() {
    const status = document.getElementById('bankDetailsStatus');
    if (!employeeRecordId) {
        status.textContent = 'Save or load the employee first.';
        return;
    }
    const input = document.getElementById('bank_password');
    const password = input.value;
    input.value = '';
    const version = ++bankRequestVersion;
    const button = document.getElementById('loadBankDetails');
    button.disabled = true;
    status.textContent = 'Loading…';
    try {
        const response = await fetch(@json(url('/hr/person/employees')) + '/' + employeeRecordId + '/bank-details', {
            method: 'POST',
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
            body: JSON.stringify({bank_password: password}),
            cache: 'no-store',
        });
        const result = await response.json();
        if (version !== bankRequestVersion) return;
        if (!response.ok) {
            status.textContent = result.errors ? Object.values(result.errors).flat().join(' ') : 'Unable to load bank details.';
            return;
        }
        for (const [field, value] of Object.entries(result.bank)) document.getElementById(field).value = value ?? '';
        status.textContent = 'Bank details loaded. Re-enter your password to save changes.';
    } catch {
        if (version === bankRequestVersion) status.textContent = 'Unable to load bank details. Please try again.';
    } finally {
        button.disabled = false;
    }
}

function populateEmployeeDetails(employee) {
    clearBankFields();
    clearEmployeePhoto();
    photoRemoved = false;
    document.querySelectorAll('[data-employee-panel] [name]').forEach(input => {
        if (input.type === 'file') return;
        if (input.type === 'checkbox') input.checked = Boolean(Number(employee[input.name]));
        else input.value = employee[input.name] ?? '';
        input.setCustomValidity('');
    });
    populateEmployeeDocuments(employee.documents ?? {});
    filterBranchDepartments();
    if (employee.photo_url) {
        const preview = document.getElementById('employeePhotoPreview');
        preview.src = employee.photo_url;
        preview.hidden = false;
        document.getElementById('employeePhotoPlaceholder').style.display = 'none';
        document.getElementById('removeEmployeePhoto').hidden = false;
    }
}

async function lookupEmployeeDetails() {
    clearTimeout(employeeLookupTimer);
    const id = document.getElementById('employee_id').value.trim();
    if (!id || id === loadedEmployeeId) return true;
    if (employeeLookupRequest?.id === id) return employeeLookupRequest.promise;
    const status = document.getElementById('employeeLookupStatus');
    status.textContent = 'Looking up employee…';
    const promise = (async () => {
        try {
            const url = new URL(@json(route('hr.person.lookup')));
            url.searchParams.set('employee_id', id);
            const response = await fetch(url, {headers: {'Accept': 'application/json'}, cache: 'no-store'});
            if (document.getElementById('employee_id').value.trim() !== id) return false;
            if (response.status === 404) {
                status.textContent = 'New Employee ID. Enter the details to register a new employee.';
                return true;
            }
            if (!response.ok) throw new Error('Lookup failed');
            const result = await response.json();
            if (document.getElementById('employee_id').value.trim() !== id) return false;
            populateEmployeeDetails(result.employee);
            employeeRecordId = result.employee.id;
            loadedEmployeeId = result.employee.employee_id;
            const pageUrl = new URL(window.location.href);
            pageUrl.searchParams.set('employee', employeeRecordId);
            window.history.replaceState(null, '', pageUrl);
            status.textContent = 'Employee details loaded. Saving will update this employee.';
            document.getElementById('employeeSaveStatus').textContent = '';
            return true;
        } catch {
            if (document.getElementById('employee_id').value.trim() === id) {
                status.textContent = 'Unable to load employee details. Please try again.';
            }
            return false;
        } finally {
            if (employeeLookupRequest?.id === id) employeeLookupRequest = null;
        }
    })();
    employeeLookupRequest = {id, promise};
    return promise;
}
let employeePhotoUrl = null;
let employeePhotoVersion = 0;

function clearEmployeePhoto() {
    photoRemoved = true;
    employeePhotoVersion++;
    document.getElementById('employeePhoto').value = '';
    const preview = document.getElementById('employeePhotoPreview');
    preview.hidden = true;
    preview.removeAttribute('src');
    document.getElementById('employeePhotoPlaceholder').style.display = '';
    document.getElementById('removeEmployeePhoto').hidden = true;
    document.getElementById('employeePhotoError').textContent = '';
    if (employeePhotoUrl) URL.revokeObjectURL(employeePhotoUrl);
    employeePhotoUrl = null;
}

async function previewEmployeePhoto() {
    const input = document.getElementById('employeePhoto');
    const file = input.files[0];
    if (!file) return;
    const version = ++employeePhotoVersion;
    const error = document.getElementById('employeePhotoError');
    const rejectPhoto = message => {
        clearEmployeePhoto();
        error.textContent = message;
    };
    if (file.size > 5 * 1024 * 1024) {
        rejectPhoto('Photo must be 5MB or smaller.');
        return;
    }
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        rejectPhoto('Please select a JPG, PNG or WebP photo.');
        return;
    }
    const url = URL.createObjectURL(file);
    const image = new Image();
    image.src = url;
    try {
        await image.decode();
        if (version !== employeePhotoVersion) {
            URL.revokeObjectURL(url);
            return;
        }
        if (employeePhotoUrl) URL.revokeObjectURL(employeePhotoUrl);
        employeePhotoUrl = url;
        photoRemoved = false;
        const preview = document.getElementById('employeePhotoPreview');
        preview.src = url;
        preview.hidden = false;
        document.getElementById('employeePhotoPlaceholder').style.display = 'none';
        document.getElementById('removeEmployeePhoto').hidden = false;
        error.textContent = '';
    } catch {
        URL.revokeObjectURL(url);
        if (version === employeePhotoVersion) rejectPhoto('This photo could not be opened. Please select another image.');
    }
}

function validateEmployeeIdentity() {
    for (const id of ['employee_id', 'employee_name']) {
        const input = document.getElementById(id);
        input.setCustomValidity(input.value.trim() ? '' : 'Please enter ' + (id === 'employee_id' ? 'an Employee ID.' : 'the full name.'));
        if (!input.checkValidity()) {
            showEmployeeTab('basic');
            input.reportValidity();
            input.focus();
            return false;
        }
    }
    return true;
}

function filterBranchDepartments() {
    const branch = document.getElementById('employee_branch');
    const department = document.getElementById('employee_department');
    Array.from(department.options).forEach(option => {
        if (!option.value) return;
        const matches = option.dataset.branchId === branch.value;
        option.hidden = !matches;
        option.disabled = !matches;
    });
    if (department.selectedOptions[0]?.disabled) department.value = '';
    department.disabled = !branch.value;
}

function showEmployeeTab(tabId) {
    if (!employeeTabIds.includes(tabId)) return;
    if (employeeSaving) return;
    if (!['basic', 'employment'].includes(tabId) && !validateEmployeeIdentity()) return;
    if (!['basic', 'employment'].includes(tabId) && !employeeRecordId) {
        document.getElementById('employeeSaveStatus').textContent = 'Please save Basic Information first.';
        return;
    }
    if (currentEmployeeTab === 'private' && tabId !== 'private') clearBankFields();
    document.getElementById('employeeSaveStatus').textContent = '';

    currentEmployeeTab = tabId;

    document.querySelectorAll('[data-employee-tab]').forEach(button => {
        const active = button.dataset.employeeTab === tabId;

        button.classList.toggle('border-rose-600', active);
        button.classList.toggle('text-rose-600', active);

        button.classList.toggle('border-transparent', !active);
        button.classList.toggle('text-slate-500', !active);

        button.setAttribute(
            'aria-selected',
            active ? 'true' : 'false'
        );
    });

    document.querySelectorAll('[data-employee-panel]').forEach(panel => {
        panel.hidden = panel.dataset.employeePanel !== tabId;
    });

    updateEmployeeFooter();
}

function updateEmployeeFooter() {
    const index = employeeTabIds.indexOf(currentEmployeeTab);
    const total = employeeTabIds.length;

    const previousButton =
        document.getElementById('previousEmployeeButton');

    const saveButton =
        document.getElementById('saveEmployeeButton');

    const stepText =
        document.getElementById('employeeStepText');

    const progress =
        document.getElementById('employeeStepProgress');

    const isFirst = index === 0;


    previousButton.classList.toggle('hidden', isFirst);
    previousButton.classList.toggle('inline-flex', !isFirst);

    const available = ['basic', 'private', 'documents'].includes(currentEmployeeTab);
    saveButton.style.display = available ? 'inline-flex' : 'none';
    saveButton.textContent = 'Save ' + document.getElementById('tab-' + currentEmployeeTab).textContent.trim();

    stepText.textContent = `Step ${index + 1} of ${total}`;

    progress.style.width =
        `${((index + 1) / total) * 100}%`;
}

function previousEmployeeTab() {
    const index = employeeTabIds.indexOf(currentEmployeeTab);

    if (index > 0) {
        showEmployeeTab(employeeTabIds[index - 1]);
    }
}

async function saveEmployee() {
    if (employeeSaving || !validateEmployeeIdentity()) return;
    if (!await lookupEmployeeDetails() || employeeSaving) return;
    const section = currentEmployeeTab;
    if (section === 'documents') {
        for (const input of document.querySelectorAll('#panel-documents input, #panel-documents textarea')) {
            if (!input.reportValidity()) return;
        }
    }
    if (!['basic', 'private', 'documents'].includes(section)) return;
    const status = document.getElementById('employeeSaveStatus');
    const data = new FormData();
    data.append('section', section);
    if (employeeRecordId) data.append('record_id', employeeRecordId);
    for (const input of document.getElementById('panel-' + section).querySelectorAll('input[name], select[name], textarea[name]')) {
        if (input.type === 'file') {
            for (const file of input.files) data.append(input.name, file);
        } else {
            if (input.name === 'remove_documents[]' && !input.checked) continue;
            data.append(input.name, input.type === 'checkbox' ? (input.name === 'remove_documents[]' ? input.value : (input.checked ? '1' : '0')) : (input.type === 'password' ? input.value : input.value.trim()));
        }
    }
    if (section === 'basic') data.append('remove_photo', photoRemoved ? '1' : '0');
    const bankPassword = document.getElementById('bank_password');
    if (bankPassword) bankPassword.value = '';
    const button = document.getElementById('saveEmployeeButton');
    employeeSaving = true;
    document.getElementById('employee_id').disabled = true;
    button.disabled = true;
    status.textContent = 'Saving…';
    try {
        const response = await fetch(@json(route('hr.person.sections.store')), {
            method: 'POST',
            headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
            body: data,
        });
        const result = await response.json();
        if (!response.ok) {
            status.textContent = result.errors ? Object.values(result.errors).flat().join('\n') : (result.message || 'Unable to save this section.');
            return;
        }
        employeeRecordId = result.id;
        loadedEmployeeId = document.getElementById('employee_id').value.trim();
        const url = new URL(window.location.href);
        url.searchParams.set('employee', result.id);
        window.history.replaceState(null, '', url);
        if (section === 'basic') {
            window.location.replace(@json(route('hr.person.create')));
            return;
        }
        status.textContent = document.getElementById('tab-' + section).textContent.trim() + ' saved successfully.';
        if (section === 'private') clearBankFields();
        if (section === 'documents') populateEmployeeDocuments(result.documents ?? {});
    } catch {
        status.textContent = 'Unable to save. Please check your connection and try again.';
    } finally {
        employeeSaving = false;
        document.getElementById('employee_id').disabled = false;
        button.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('employmentHistoryForm').addEventListener('submit', saveEmploymentPeriod);
    document.getElementById('historyBranch').addEventListener('change', filterHistoryDepartments);
    document.getElementById('historyStart').addEventListener('change', function () {
        document.getElementById('historyEnd').min = this.value;
    });
    document.getElementById('historyAction').addEventListener('change', function () {
        const end = document.getElementById('historyEnd');
        end.disabled = this.value === 'transfer';
        if (end.disabled) end.value = '';
    });
    filterHistoryDepartments();
    document.getElementById('employmentDetailsSearch').addEventListener('submit', function (event) {
        event.preventDefault();
        viewEmploymentDetails();
    });
    document.getElementById('employmentLookupId').addEventListener('input', function () {
        ++employmentLookupVersion;
        document.getElementById('employmentDetailsResult').hidden = true;
        document.getElementById('employmentDetailsStatus').textContent = '';
        clearTimeout(employmentLookupTimer);
        if (this.value.trim()) employmentLookupTimer = setTimeout(viewEmploymentDetails, 500);
    });
    populateEmployeeDocuments(@json($employeeDocuments));
    document.querySelectorAll('[data-document-category] input[type="file"]').forEach(input => {
        input.addEventListener('change', () => {
            const files = Array.from(input.files);
            const invalid = files.length > 10 || files.some(file => file.size > 5 * 1024 * 1024 || !/\.(pdf|jpe?g|png|webp)$/i.test(file.name));
            const message = invalid ? 'Choose up to 10 PDF or image files, each 5MB or smaller.' : files.map(file => file.name).join(', ');
            if (invalid) input.value = '';
            input.closest('[data-document-category]').querySelector('[data-file-selection]').textContent = message;
        });
    });
    const employeeIdInput = document.getElementById('employee_id');
    employeeIdInput.addEventListener('input', function () {
        clearTimeout(employeeLookupTimer);
        if (employeeRecordId && this.value.trim() !== loadedEmployeeId) {
            const id = this.value;
            populateEmployeeDetails({employee_id: id});
            employeeRecordId = null;
            loadedEmployeeId = null;
            const url = new URL(window.location.href);
            url.searchParams.delete('employee');
            window.history.replaceState(null, '', url);
        }
        document.getElementById('employeeLookupStatus').textContent = '';
        if (this.value.trim()) employeeLookupTimer = setTimeout(lookupEmployeeDetails, 500);
    });
    employeeIdInput.addEventListener('blur', lookupEmployeeDetails);
    employeeIdInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            lookupEmployeeDetails();
        }
    });
    document.getElementById('employeePhoto').addEventListener('change', previewEmployeePhoto);
    document.getElementById('employee_branch').addEventListener('change', filterBranchDepartments);
    if (savedEmployee) {
        if (savedEmployee.profile_photo) {
            const preview = document.getElementById('employeePhotoPreview');
            preview.src = @json($employee ? route('hr.person.registration.photo', $employee) : null);
            preview.hidden = false;
            document.getElementById('employeePhotoPlaceholder').style.display = 'none';
            document.getElementById('removeEmployeePhoto').hidden = false;
        }
        document.querySelectorAll('[data-employee-panel] [name]').forEach(input => {
            if (input.type === 'file' || savedEmployee[input.name] == null) return;
            if (input.type === 'checkbox') input.checked = Boolean(Number(savedEmployee[input.name]));
            else input.value = savedEmployee[input.name];
        });
    }
    filterBranchDepartments();
    for (const id of ['employee_id', 'employee_name']) {
        document.getElementById(id).addEventListener('input', function () {
            this.setCustomValidity('');
        });
    }
    showEmployeeTab('basic');
});

</script>

</x-layouts.app>
