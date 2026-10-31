
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
                    {{ $stagingEnrollment ? 'Review Enrollment' : 'Add Person' }}
                </h1>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $stagingEnrollment ? 'Complete the profile and promote this device enrollment.' : 'Register a new employee' }}
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
                    ['id' => 'basic', 'label' => 'Basic Information '],
                    ['id' => 'private', 'label' => 'Private Information'],
                    ['id' => 'access', 'label' => 'Access Level'],
                    // ['id' => 'schedule', 'label' => 'Schedule'],
                    ['id' => 'documents', 'label' => 'Documents & Qualifications'],
                    ['id' => 'extra', 'label' => 'Additional Information'],
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
                        <div id="probationEndDateGroup" hidden class="grid gap-2 sm:grid-cols-[160px_minmax(0,1fr)] sm:items-center">
                            <label for="probation_end_date" class="text-sm font-medium text-slate-700">Probation End Date</label>
                            <input id="probation_end_date" name="probation_end_date" type="date" value="{{ old('probation_end_date') }}" disabled class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm">
                        </div>
                        <div id="confirmedDateGroup" hidden class="grid gap-2 sm:grid-cols-[160px_minmax(0,1fr)] sm:items-center">
                            <label for="confirmed_date" class="text-sm font-medium text-slate-700">Confirmed Date</label>
                            <div>
                                <input id="confirmed_date" name="confirmed_date" type="date" value="{{ old('confirmed_date') }}" max="{{ now('Asia/Colombo')->toDateString() }}" disabled class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm">
                                <p class="mt-1 text-xs text-slate-500">Required when confirming an employee from Probation to Permanent.</p>
                            </div>
                        </div>
                        @include('hr.person.partials.biometric-promotion')
                        {{-- SELF SERVICE --}}
                        <div @if($stagingEnrollment) hidden @endif class="grid gap-2 sm:grid-cols-[160px_minmax(0,1fr)] sm:items-center">

                            <label for="self_service"
                                   class="text-sm font-medium text-slate-700">
                                Allow Login to Self-Service
                            </label>

                            <label class="inline-flex cursor-pointer items-center gap-3">

                                <input
                                    type="checkbox"
                                    id="self_service"
                                    @if($stagingEnrollment) disabled @endif
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

                @if(auth()->user()?->canManageEmploymentStructure())
                    <p class="mt-6 text-xs text-slate-500">Manage dated designation, department and reporting manager assignments in <button type="button" onclick="openEmployeePositionAssignment()" class="font-semibold text-slate-700 underline">Employment Details</button> after saving or loading an employee.</p>
                @endif
                @include('hr.person.partials.employee-resignation')
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

                    @if($tab['id'] !== 'documents')
                        <h2 class="text-lg font-bold text-slate-900">
                            {{ $tab['label'] }}
                        </h2>
                    @endif

                    @if($tab['id'] === 'private')
                        @include('hr.person.partials.private-information')
                    @elseif($tab['id'] === 'employment')
                        @include('hr.person.partials.employment-details')
                    @elseif($tab['id'] === 'extra')
                        @include('hr.person.partials.additional-information')
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
                class="erp-primary-action inline-flex min-h-10 items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white sm:px-7"
            >
                Save Basic Information
            </button>

        </div>
    </div>
</div>


</div>

@include('hr.person.partials.document-type-dialog')

<script>

const employeeTabIds = @json(array_column($tabs, 'id'));

let currentEmployeeTab = 'basic';
const savedEmployee = @json($employee);
@php
    $pendingEnrollmentData = $stagingEnrollment ? array_merge(
        $stagingEnrollment->only(['id', 'raw_enrollment_id', 'raw_name', 'branch_id']),
        ['department_id' => $stagingEnrollment->device->branch_id === $stagingEnrollment->branch_id ? $stagingEnrollment->device->department_id : null]
    ) : null;
@endphp
const pendingEnrollment = @json($pendingEnrollmentData);
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
            row.className = 'dq-saved-file';
            row.dataset.documentFile = file.id;
            const link = document.createElement('a');
            link.href = file.url;
            link.textContent = file.name;
            link.className = 'min-w-0 break-all font-medium text-slate-700 underline hover:text-slate-900';
            const label = document.createElement('label');
            label.className = 'dq-remove';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'remove_documents[]';
            checkbox.value = file.id;
            label.append(checkbox, document.createTextNode('Remove'));
            row.append(link);
            if (card.dataset.requiresExpiry === '1') {
                const expiry = document.createElement('input');
                expiry.type = 'date';
                expiry.name = `document_expiry[${file.id}]`;
                expiry.value = file.expires_on ?? '';
                expiry.setAttribute('aria-label', `Expiry date for ${file.name}`);
                expiry.className = 'min-w-0 rounded border-slate-300 text-xs';
                const expiryLabel = document.createElement('label');
                expiryLabel.className = 'text-xs text-slate-500';
                expiryLabel.append(document.createTextNode('Expiry date'), expiry);
                row.classList.add('flex-wrap');
                row.append(expiryLabel);
            }
            row.append(label);
            list.append(row);
        });
        card.querySelector('input[type="file"]').value = '';
        const uploadExpiry = card.querySelector('[data-upload-expiry]');
        if (uploadExpiry) uploadExpiry.value = '';
        card.querySelector('[data-file-selection]').textContent = '';
    });
    refreshDocumentChecklist();
}

function openDocumentTypeSettings() {
    const dialog = document.getElementById('documentTypeSettingsDialog');
    if (!dialog) return;
    dialog.querySelectorAll('[data-document-type-employee]').forEach(input => input.value = employeeRecordId ?? '');
    const url = new URL(window.location.href);
    url.searchParams.set('tab', 'documents');
    window.history.replaceState(null, '', url);
    dialog.showModal();
}

function selectDocumentType(code) {
    const picker = document.getElementById('documentTypePicker');
    const selected = Array.from(picker.options).find(option => option.value === code);
    if (!selected) return;
    document.querySelectorAll('[name="document_category_picker"]').forEach(radio => {
        radio.checked = radio.value === selected.dataset.pickerGroup;
    });
    filterDocumentTypes(code);
}

function filterDocumentTypes(preferred = null) {
    const group = document.querySelector('[name="document_category_picker"]:checked')?.value;
    const picker = document.getElementById('documentTypePicker');
    const available = Array.from(picker.options).filter(option => option.dataset.pickerGroup === group);
    for (const option of picker.options) {
        option.hidden = option.dataset.pickerGroup !== group;
        option.disabled = option.hidden;
    }
    picker.value = available.some(option => option.value === preferred) ? preferred : (available[0]?.value ?? '');
    picker.hidden = !available.length;
    document.getElementById('documentTypeEmpty').hidden = available.length > 0;
    showDocumentEditor();
}

function showDocumentEditor() {
    const code = document.getElementById('documentTypePicker').value;
    document.querySelectorAll('[data-document-category]').forEach(card => card.hidden = card.dataset.documentCategory !== code);
    document.querySelectorAll('[data-checklist-type]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.checklistType === code)));
}

function refreshDocumentChecklist() {
    const list = document.getElementById('documentChecklist');
    list.replaceChildren();
    let total = 0;
    document.querySelectorAll('[data-document-category]').forEach(card => {
        const count = card.querySelectorAll('[data-saved-documents] > div').length;
        total += count;
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.checklistType = card.dataset.documentCategory;
        const name = document.createElement('span');
        name.textContent = card.querySelector('.dq-type-title').childNodes[0].textContent.trim();
        const number = document.createElement('span');
        number.textContent = count;
        button.append(name, number);
        button.addEventListener('click', () => selectDocumentType(card.dataset.documentCategory));
        list.append(button);
    });
    document.getElementById('documentSavedTotal').textContent = total;
    showDocumentEditor();
}

function populateOnboarding(status) {
    const label = document.getElementById('onboardingStatus');
    const button = document.getElementById('completeOnboarding');
    if (!status) {
        label.textContent = 'Save or load an employee to check onboarding documents.';
        button.disabled = true;
        return;
    }
    label.textContent = status.completed ? 'Onboarding completed.' : (status.can_complete ? 'Mandatory documents are ready. You can complete onboarding.' : 'Onboarding blocked. Valid documents needed: ' + status.blocking_types.join(', '));
    button.disabled = status.completed || !status.can_complete;
}

async function completeEmployeeOnboarding() {
    if (!employeeRecordId || employeeSaving) return;
    const button = document.getElementById('completeOnboarding');
    button.disabled = true;
    employeeSaving = true;
    try {
        const response = await fetch({{ Illuminate\Support\Js::from(url('/hr/person/add-employee')) }} + '/' + employeeRecordId + '/complete-onboarding', {
            method: 'POST', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}
        });
        const result = await response.json();
        if (!response.ok) throw new Error(Object.values(result.errors ?? {}).flat().join(' ') || result.message || 'Unable to complete onboarding.');
        populateOnboarding(result.onboarding);
    } catch (error) {
        document.getElementById('onboardingStatus').textContent = error.message;
        button.disabled = false;
    } finally {
        employeeSaving = false;
    }
}

function populateEmployeeCertificates(documents = {}) {
    const list = document.getElementById('employeeCertificatesResult');
    list.replaceChildren();
    const types = @json($documentTypes->map->only(['code', 'name', 'requires_expiry'])->values());
    let submittedTypes = 0;
    let copies = 0;
    for (const type of types) {
        const category = documents[type.code] ?? {};
        const files = category.files ?? [];
        const submitted = files.length > 0;
        if (submitted) submittedTypes++;
        copies += files.length;
        const card = document.createElement('article');
        card.className = 'min-w-0 rounded-lg border border-slate-200 bg-white p-3';
        const header = document.createElement('div');
        header.className = 'flex flex-wrap items-center justify-between gap-2';
        const title = document.createElement('h4');
        title.className = 'text-xs font-semibold text-slate-800';
        title.textContent = type.name;
        const badge = document.createElement('span');
        badge.className = submitted ? 'rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700' : 'rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700';
        badge.textContent = submitted ? '✓ Submitted' : 'Not submitted';
        header.append(title, badge);
        card.append(header);
        const qualification = category.qualification;
        if (qualification) {
            const details = document.createElement('p');
            details.className = 'mt-2 whitespace-pre-wrap break-words text-xs text-slate-500';
            details.textContent = [qualification.institution, qualification.year, qualification.details].filter(Boolean).join(' · ');
            if (details.textContent) card.append(details);
        }
        for (const file of files) {
            const row = document.createElement('div');
            row.className = 'mt-2 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-2';
            const link = document.createElement('a');
            link.href = file.url;
            link.className = 'min-w-0 break-all text-xs font-medium text-slate-600 underline hover:text-slate-900';
            link.textContent = file.name;
            link.setAttribute('aria-label', `Download ${file.name}`);
            row.append(link);
            if (type.requires_expiry && file.expires_on) {
                const expiry = document.createElement('span');
                expiry.className = 'text-xs text-slate-500';
                expiry.textContent = 'Expiry: ' + file.expires_on;
                row.append(expiry);
            }
            card.append(row);
        }
        list.append(card);
    }
    document.getElementById('employeeCertificatesSummary').textContent = `${submittedTypes} of ${types.length} document types submitted · ${copies} file${copies === 1 ? '' : 's'}`;
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
    document.getElementById('employeeCertificatesResult').replaceChildren();
    document.getElementById('employeeCertificatesSummary').textContent = '';
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
        populateEmploymentPeriodForm(employee);
        populateEmployeeCertificates(employee.documents ?? {});
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
            row.className = 'rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs leading-6';
            row.textContent = `${item.start_date ?? '—'} – ${item.end_date ?? 'Present'} | ${item.department ?? '—'} | ${item.designation ?? '—'} | Manager: ${item.manager ?? '—'} | ${item.change_reason ?? ''}`;
            history.appendChild(row);
        }
        status.textContent = 'Employee details loaded.';
        result.hidden = false;
    } catch {
        if (version === employmentLookupVersion) status.textContent = 'Unable to load employee details. Please try again.';
    }
}

function populateEmploymentPeriodForm(employee) {
    const form = document.getElementById('employmentPeriodForm');
    if (!form) return;
    document.getElementById('employmentPeriodEditor').hidden = employee.status === 'Resigned';
    form.reset();
    document.getElementById('historySaveStatus').textContent = '';
    const current = employee.employment_history?.find(period => !period.end_date);
    const action = document.getElementById('historyAction');
    action.querySelector('[value="initial"]').disabled = Boolean(current);
    action.querySelector('[value="transfer"]').disabled = !current;
    action.value = current ? 'transfer' : 'initial';
    document.getElementById('historyBranch').value = employee.branch_id ?? '';
    filterHistoryDepartments();
    document.getElementById('historyDepartment').value = employee.department_id ?? '';
    for (const option of document.getElementById('historyManager').options) {
        option.disabled = option.value === String(employee.id);
        option.hidden = option.disabled;
    }
    document.getElementById('historyManager').value = employee.line_manager_id ?? '';
    configureHistoryAction();
}

function configureHistoryAction() {
    if (!historyEmployee) return;
    const action = document.getElementById('historyAction').value;
    const current = historyEmployee.employment_history?.find(period => !period.end_date);
    const historical = action === 'historical';
    const end = document.getElementById('historyEndDate');
    document.getElementById('historyEndDateGroup').hidden = !historical;
    end.disabled = !historical;
    end.required = historical;
    end.value = '';
    const start = document.getElementById('historyStartDate');
    start.min = historyEmployee.joined_date?.slice(0, 10) ?? '';
    if (action === 'transfer' && current) {
        const nextDay = new Date(current.start_date + 'T00:00:00Z');
        nextDay.setUTCDate(nextDay.getUTCDate() + 1);
        start.min = nextDay.toISOString().slice(0, 10);
    }
    start.value = action === 'initial' ? (historyEmployee.joined_date?.slice(0, 10) ?? '') : '';
    document.getElementById('historyReason').value = action === 'initial' ? 'Initial appointment' : '';
    const designation = document.getElementById('historyDesignation');
    for (const option of designation.options) {
        const retained = action === 'transfer' && option.value === String(current?.designation_id);
        option.disabled = option.value !== '' && option.dataset.active !== '1' && !retained;
        option.hidden = option.disabled;
    }
    designation.value = action === 'transfer' ? String(current?.designation_id ?? '') : '';
}

async function openEmployeePositionAssignment() {
    if (!employeeRecordId || employeeSaving) return;
    showEmployeeTab('employment');
    document.getElementById('employmentLookupId').value = document.getElementById('employee_id').value.trim();
    await viewEmploymentDetails();
    const editor = document.getElementById('employmentPeriodEditor');
    if (editor && historyEmployee && !editor.hidden) {
        editor.open = true;
        editor.scrollIntoView({block: 'nearest', behavior: 'smooth'});
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
        for (const id of ['promotionLoginPassword', 'promotionLoginConfirmation']) { const input = document.getElementById(id); if (input) input.value = ''; }
        if (!response.ok) {
            status.textContent = result.errors ? Object.values(result.errors).flat().join('\n') : (result.message || 'Unable to save employment period.');
            return;
        }
        await viewEmploymentDetails();
        if (historyEmployee && employeeRecordId === employee.id) {
            document.getElementById('employee_branch').value = historyEmployee.branch_id;
            filterBranchDepartments();
            document.getElementById('employee_department').value = historyEmployee.department_id;
        }
        document.getElementById('historySaveStatus').textContent = 'Employment period saved successfully.';
    } catch {
        status.textContent = 'Unable to save. Check your connection and try again.';
    } finally {
        historySaving = false;
        button.disabled = false;
        document.getElementById('employmentLookupId').disabled = false;
    }
}

function setBankAccountVisibility(visible) {
    const input = document.getElementById('bank_account_number');
    const button = document.getElementById('toggleBankAccountNumber');
    if (!input || !button) return;
    input.type = visible ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(visible));
    const label = visible ? 'Hide account number' : 'Show account number';
    button.setAttribute('aria-label', label);
    button.title = label;
    document.getElementById('bankAccountEyeSlash').style.display = visible ? '' : 'none';
}

function clearBankFields() {
    setBankAccountVisibility(false);
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

let resignationEmployee = null;
let resignationSaving = false;

function populateEmployeeResignation(employee) {
    const button = document.getElementById('openResignation');
    if (!button) return;
    resignationEmployee = employee?.id ? employee : null;
    const resigned = employee?.status === 'Resigned';
    document.getElementById('basicEmploymentStatus').textContent = employee?.id ? (employee.status || 'Active') : 'New employee';
    document.getElementById('basicEmploymentStatus').className = 'ml-2 rounded-full px-2 py-1 text-xs ' + (resigned ? 'bg-red-100 text-red-700' : 'bg-white text-slate-600');
    button.disabled = !resignationEmployee || resigned;
    document.getElementById('resignationHint').textContent = resigned
        ? `Resigned on ${employee.resigned_on ? employee.resigned_on.slice(0, 10) : '—'}. ${employee.resignation_reason ?? ''}`
        : (resignationEmployee ? 'Admin access: record this employee’s resignation.' : 'Load an employee to record their resignation.');
}

function openEmployeeResignation() {
    if (!resignationEmployee || employeeSaving || resignationSaving) return;
    if (document.getElementById('employee_id').value.trim() !== resignationEmployee.employee_id) return;
    document.getElementById('employeeResignationForm').reset();
    document.getElementById('employeeResignedOn').min = resignationEmployee.joined_date?.slice(0, 10) ?? '';
    document.getElementById('employeeResignationError').textContent = '';
    document.getElementById('resigningEmployeeName').textContent = `${resignationEmployee.name || resignationEmployee.first_name} · ${resignationEmployee.employee_id}`;
    document.getElementById('employeeResignationDialog').showModal();
}

async function submitEmployeeResignation(event) {
    event.preventDefault();
    if (!resignationEmployee || resignationSaving || employeeSaving) return;
    const employee = resignationEmployee;
    if (document.getElementById('employee_id').value.trim() !== employee.employee_id) return;
    const error = document.getElementById('employeeResignationError');
    const button = document.getElementById('confirmResignation');
    const cancel = document.getElementById('cancelResignation');
    button.disabled = true;
    cancel.disabled = true;
    employeeSaving = true;
    resignationSaving = true;
    error.textContent = '';
    try {
        const template = @json(route('hr.person.employees.resign', 0));
        const response = await fetch(template.replace('/0/resign', '/' + employee.id + '/resign'), {
            method: 'POST', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
            body: JSON.stringify({resigned_on: document.getElementById('employeeResignedOn').value, resignation_reason: document.getElementById('employeeResignationReason').value.trim()})
        });
        const result = await response.json();
        if (!response.ok) throw new Error(Object.values(result.errors ?? {}).flat().join(' ') || result.message || 'Unable to record resignation.');
        populateEmployeeResignation({...employee, ...result});
        const selfService = document.querySelector('[name="allow_self_service"]');
        if (selfService) selfService.checked = false;
        document.getElementById('employeeResignationDialog').close();
        document.getElementById('employeeSaveStatus').textContent = result.message;
    } catch (failure) {
        error.textContent = failure.message;
    } finally {
        employeeSaving = false;
        resignationSaving = false;
        button.disabled = false;
        cancel.disabled = false;
    }
}

let loadedEmploymentType = savedEmployee?.employment_type ?? null;

function updateEmploymentDateFields() {
    const type = document.getElementById('employment_type').value;
    const joined = document.getElementById('joined_date').value;
    for (const [field, group, applicable] of [
        ['probation_end_date', 'probationEndDateGroup', type === 'Probation'],
        ['confirmed_date', 'confirmedDateGroup', type === 'Permanent'],
    ]) {
        const input = document.getElementById(field);
        document.getElementById(group).hidden = !applicable;
        input.disabled = !applicable;
        input.min = joined;
    }
    document.getElementById('confirmed_date').required = type === 'Permanent' && loadedEmploymentType === 'Probation';
}

function togglePromotionLogin() {
    const checkbox = document.getElementById('promotionCreateLogin');
    if (!checkbox) return;
    const fields = document.getElementById('promotionLoginFields');
    fields.hidden = !checkbox.checked;
    fields.querySelectorAll('input').forEach(input => {
        input.disabled = !checkbox.checked;
        input.required = checkbox.checked;
        if (!checkbox.checked) input.value = '';
    });
}

function populateEmployeeDetails(employee) {
    loadedEmploymentType = employee.employment_type ?? null;
    populateEmployeeResignation(employee);
    clearBankFields();
    clearEmployeePhoto();
    photoRemoved = false;
    document.querySelectorAll('[data-employee-panel] [name]').forEach(input => {
        if (input.type === 'file' || input.type === 'radio') return;
        if (input.type === 'checkbox') input.checked = Boolean(Number(employee[input.name]));
        else input.value = input.type === 'date' ? String(employee[input.name] ?? '').slice(0, 10) : (employee[input.name] ?? '');
        input.setCustomValidity('');
    });
    document.querySelectorAll('[data-additional-field]').forEach(input => {
        input.value = employee.additional_values?.[input.dataset.additionalField] ?? '';
    });
    updateEmploymentDateFields();
    populateEmployeeDocuments(employee.documents ?? {});
        populateOnboarding(employee.onboarding);
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
    if (pendingEnrollment) return true;
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

    const available = ['basic', 'private', 'documents', 'extra'].includes(currentEmployeeTab);
    saveButton.style.display = available ? 'inline-flex' : 'none';
    saveButton.textContent = pendingEnrollment && !employeeRecordId && currentEmployeeTab === 'basic' ? 'Promote to Employee' : 'Save ' + document.getElementById('tab-' + currentEmployeeTab).textContent.trim();

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
    if (pendingEnrollment && section === 'basic') {
        for (const input of document.querySelectorAll('#panel-basic input[name], #panel-basic select[name]')) {
            if (!input.disabled && !input.reportValidity()) return;
        }
    }
    if (section === 'basic') {
        for (const id of ['probation_end_date', 'confirmed_date']) {
            const input = document.getElementById(id);
            if (!input.disabled && !input.reportValidity()) return;
        }
    }
    if (section === 'extra') {
        for (const input of document.querySelectorAll('#panel-extra [data-additional-field]')) {
            if (!input.reportValidity()) return;
        }
    }
    if (section === 'documents') {
        for (const input of document.querySelectorAll('#panel-documents input, #panel-documents textarea')) {
            if (!input.checkValidity()) {
                const card = input.closest('[data-document-category]');
                if (card) selectDocumentType(card.dataset.documentCategory);
                input.reportValidity();
                return;
            }
        }
    }
    if (!['basic', 'private', 'documents', 'extra'].includes(section)) return;
    const status = document.getElementById('employeeSaveStatus');
    const data = new FormData();
    data.append('section', section);
    if (employeeRecordId) data.append('record_id', employeeRecordId);
    for (const input of document.getElementById('panel-' + section).querySelectorAll('input[name], select[name], textarea[name]')) {
        if (input.disabled) continue;
        if (input.type === 'file') {
            for (const file of input.files) data.append(input.name, file);
        } else {
            if (input.name === 'remove_documents[]' && !input.checked) continue;
            data.append(input.name, input.type === 'checkbox' ? (input.name === 'remove_documents[]' ? input.value : (input.checked ? '1' : '0')) : (input.type === 'password' ? input.value : input.value.trim()));
        }
    }
    if (section === 'basic') data.append('remove_photo', photoRemoved ? '1' : '0');
    if (pendingEnrollment && section === 'basic') data.set('branch_id', pendingEnrollment.branch_id);
    const bankPassword = document.getElementById('bank_password');
    if (bankPassword) bankPassword.value = '';
    const button = document.getElementById('saveEmployeeButton');
    employeeSaving = true;
    document.getElementById('employee_id').disabled = true;
    button.disabled = true;
    status.textContent = 'Saving…';
    try {
        const endpoint = pendingEnrollment && section === 'basic'
            ? @json(route('hr.person.enrollments.promote', 0)).replace('/0/promote', '/' + pendingEnrollment.id + '/promote')
            : @json(route('hr.person.sections.store'));
        const response = await fetch(endpoint, {
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
            window.location.replace(result.redirect_url ?? @json(route('hr.person.create')));
            return;
        }
        status.textContent = document.getElementById('tab-' + section).textContent.trim() + ' saved successfully.';
        if (section === 'private') clearBankFields();
        if (section === 'documents') { populateEmployeeDocuments(result.documents ?? {}); populateOnboarding(result.onboarding); }
    } catch {
        status.textContent = 'Unable to save. Please check your connection and try again.';
    } finally {
        employeeSaving = false;
        document.getElementById('employee_id').disabled = false;
        button.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', function () {
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
    document.querySelectorAll('[name="document_category_picker"]').forEach(radio => radio.addEventListener('change', () => filterDocumentTypes()));
    document.getElementById('documentTypePicker').addEventListener('change', showDocumentEditor);
    filterDocumentTypes();
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
    populateOnboarding(@js($onboarding));
    document.getElementById('employeePhoto').addEventListener('change', previewEmployeePhoto);
    document.getElementById('employee_branch').addEventListener('change', filterBranchDepartments);
    populateEmployeeResignation(savedEmployee);
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
            else input.value = input.type === 'date' ? String(savedEmployee[input.name]).slice(0, 10) : savedEmployee[input.name];
        });
    }
    if (pendingEnrollment) {
        populateEmployeeDetails({employee_id: pendingEnrollment.raw_enrollment_id, name: pendingEnrollment.raw_name ?? '', branch_id: pendingEnrollment.branch_id, department_id: pendingEnrollment.department_id});
        employeeIdInput.readOnly = true;
        document.getElementById('employee_branch').disabled = true;
        document.getElementById('joined_date').value = @json(now('Asia/Colombo')->toDateString());
        document.getElementById('employeeLookupStatus').textContent = 'Enrollment ID is retained from the device. Review the details before promoting.';
        togglePromotionLogin();
    }
    updateEmploymentDateFields();
    document.getElementById('employment_type').addEventListener('change', updateEmploymentDateFields);
    document.getElementById('joined_date').addEventListener('change', updateEmploymentDateFields);
    for (const id of ['employee_id', 'employee_name']) {
        document.getElementById(id).addEventListener('input', function () {
            this.setCustomValidity('');
        });
    }
    const requestedTab = new URL(window.location.href).searchParams.get('tab');
    showEmployeeTab(employeeTabIds.includes(requestedTab) ? requestedTab : 'basic');
    if (@json(old('document_type_context') === 'registration')) openDocumentTypeSettings();
    const destination = new URL(window.location.href).searchParams;
    const requestedDocumentType = destination.get('document_type');
    const requestedDocumentFile = destination.get('document_file');
    function revealNotificationDocument() {
        if (!requestedDocumentType) return;
        showEmployeeTab('documents');
        selectDocumentType(requestedDocumentType);
        const file = Array.from(document.querySelectorAll('[data-document-file]')).find(row => row.dataset.documentFile === requestedDocumentFile);
        if (file) {
            file.style.outline = '2px solid #94a3b8';
            file.style.outlineOffset = '2px';
            file.scrollIntoView({block: 'center', behavior: 'smooth'});
        }
    }
    if (savedEmployee) revealNotificationDocument();
    const lookup = new URL(window.location.href).searchParams.get('lookup');
    if (lookup && !savedEmployee) {
        employeeIdInput.value = lookup;
        lookupEmployeeDetails().then(() => { showEmployeeTab('documents'); revealNotificationDocument(); });
    }
});

</script>

</x-layouts.app>
