
<x-layouts.app title="Person Management" section="person">

@php
    $activeBranch = isset($selectedBranch) && $selectedBranch !== '' ? (int) $selectedBranch : null;
    $activeDepartment = isset($selectedDepartment) && $selectedDepartment !== '' ? (int) $selectedDepartment : null;
@endphp

<style>
    /* Scoped layout: does not change the ERP navigation or main sidebar. */
    
.person-workspace {
    display: grid;
    grid-template-columns: 240px minmax(0, 1fr);
    width: 100%;
    min-width: 0;
    height: 100%;
    overflow: hidden;
}

.person-organization {
    display: flex;
    flex-direction: column;
    width: 240px;
    min-width: 0;
    height: 100%;
    overflow: hidden;
    background: #f8fafc;
    border-right: 1px solid #e2e8f0;
}

.person-organization-list {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
}

.person-directory {
    display: flex;
    flex-direction: column;
    width: 100%;
    min-width: 0;
    height: 100%;
    overflow: hidden;
    background: #ffffff;
}

.person-table-scroll {
    flex: 1;
    min-height: 0;
    min-width: 0;
    overflow: auto;
}

.person-table {
    min-width: 760px;
}

@media (max-width: 900px) {
    .person-workspace {
        grid-template-columns: 210px minmax(0, 1fr);
    }

    .person-organization {
        width: 210px;
    }
}

@media (max-width: 700px) {
    .person-workspace {
        display: flex;
        flex-direction: column;
        height: auto;
        min-height: calc(100dvh - 54px);
        overflow: visible;
    }

    .person-organization {
        width: 100%;
        height: auto;
        max-height: 300px;
        border-right: 0;
        border-bottom: 1px solid #e2e8f0;
    }

    .person-directory {
        height: auto;
        min-height: 450px;
    }

    .person-table-scroll {
        overflow-x: auto;
    }
}

    .person-table { min-width: 760px; }
    /* Explicit component styles: buttons remain visible even if Tailwind's
       dynamic utilities were not included in the compiled CSS. */
    .person-organization { background: #f8fafc; }
    .person-directory { display:flex; flex-direction:column; background:#fff; }
    .person-org-top { padding:16px; background: #ffffff;; border-bottom:1px solid #e2e8f0; }
    .person-org-heading { display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .person-org-title { font-size:16px; font-weight:800; color:#0f172a; }
    .person-org-subtitle { font-size:12px; color:#64748b; margin-top:3px; }
    .person-action { display:inline-flex !important; align-items:center; justify-content:center; gap:7px; flex-shrink:0; border:0; border-radius:10px; padding:10px 14px; font-size:12px; font-weight:800; line-height:1.2; color:#fff !important; cursor:pointer; text-decoration:none; box-shadow:0 5px 14px rgba(225,29,72,.18); white-space:nowrap; }
    .person-action:hover { filter:brightness(.94); transform:translateY(-1px); }
    .person-action--rose { background:linear-gradient(110deg,#f43f5e,#be123c) !important; }
    .person-action--blue { background:linear-gradient(110deg,#2563eb,#4338ca) !important; }
    .person-action--green { background:linear-gradient(110deg,#059669,#047857) !important; }
   .person-hero {
    display: flex !important;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding: 16px 20px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
}
    .person-toolbar { padding:12px 20px; min-height:64px; }
    .person-toolbar-group { display:flex; align-items:center; flex-wrap:wrap; gap:12px; }
    .person-sub-departments { display:inline-flex; align-items:center; gap:8px; color:#555; font-size:14px; cursor:pointer; }
    .person-sub-departments input { width:18px; height:18px; accent-color:#ff353d; }
    .person-toolbar-divider { width:1px; height:28px; background:#ddd; }
    .person-tool { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; padding:6px; border:0; border-radius:4px; background:transparent; color:#737373; cursor:pointer; }
    .person-tool:hover { background:#f1f5f9; color:#e11d48; }
    .person-tool:focus-visible { outline:2px solid #e11d48; outline-offset:2px; }
    .person-tool svg { width:22px; height:22px; fill:none; stroke:currentColor; stroke-width:1.5; stroke-linecap:round; stroke-linejoin:round; }
    .person-workspace.person-hide-departments [id^="branch-departments-"] { display:none; }
    .person-workspace:fullscreen { height:100dvh; background:white; }
    .person-hero-actions { display:flex !important; gap:9px; align-items:center; flex-wrap:wrap; }
    .person-directory-bar { display:flex !important; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; padding:14px 20px; border-bottom:1px solid #e2e8f0; }
    .person-directory-bar form { display:flex !important; align-items:center; gap:8px; min-width:0; }
    .person-directory-bar input[type=search] { min-width:0; width:min(300px,40vw); border:1px solid #cbd5e1; border-radius:9px; padding:10px 12px; font-size:13px; }
    .person-directory-bar button[type=submit] { border:0; background:#0f172a; color:#fff; border-radius:9px; padding:10px 15px; font-weight:700; cursor:pointer; }
    .person-table thead { background:#0f172a; }
    .person-table thead th { color:#fff; }
    .person-table-scroll { flex:1 1 auto; }
    .person-modal.hidden { display:none !important; }
    .person-modal.flex { display:flex !important; }
    .person-modal { position:fixed !important; inset:0; z-index:1000 !important; align-items:center; justify-content:center; background:rgba(15,23,42,.7); padding:16px; }
    .person-modal-panel { background:#fff; width:100%; max-height:92dvh; overflow:hidden; border-radius:18px; box-shadow:0 20px 80px rgba(0,0,0,.25); }
    .person-modal-form { overflow-y:auto; min-height:0; }
    .person-modal-form input, .person-modal-form select, .person-modal-form textarea { max-width:100%; }
    @media(max-width:700px) {
      .person-hero { align-items:stretch; }
      .person-hero-actions { width:100%; }
      .person-hero-actions .person-action { flex:1; }
      .person-directory-bar form { width:100%; }
      .person-directory-bar input[type=search] { width:100%; flex:1; }
    }
    @media (max-width: 900px) {
        .person-workspace {
            grid-template-columns: minmax(0, 210px) minmax(0, 1fr) !important;
        }
        .person-organization {
            width: 210px !important;
            max-width: 210px !important;
        }
    }
    @media (max-width: 700px) {
        .person-workspace {
            display: flex !important;
            flex-direction: column !important;
            min-height: 0;
        }
        .person-organization {
            width: 100% !important;
            max-width: 100% !important;
            height: auto;
            border-right: 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .person-organization-list { max-height: 260px; }
        .person-directory { min-height: 450px; }
    }
</style>

<div class="person-workspace">

    {{-- LEFT: ORGANIZATION TREE --}}
   <aside class="person-organization w-full min-w-0 shrink-0 border-b border-slate-200 bg-pink-400 lg:border-b-0 lg:border-r">

        <div class="person-org-top">
            <div class="person-org-heading">
                {{-- <div>
                    <h2 class="person-org-title">Organization</h2>
                    <p class="person-org-subtitle">Branches &amp; Departments</p>
                </div> --}}
                {{-- <button type="button" onclick="openPersonModal('departmentModal')"
                    class="person-action erp-primary-action" title="Add Department">+ Department</button> --}}
            </div>
            <input id="branchSearch" type="search" placeholder="Search branches..."
                class="mt-4 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-rose-500"
                style="width:100%;margin-top:14px;padding:10px 12px;border:1px solid #cbd5e1;border-radius:9px;">
        </div>

        <div class="person-organization-list overflow-y-auto overflow-x-hidden p-2">

            <a
                href="{{ route('hr.person.index') }}"
                class="mb-2 flex items-center justify-between rounded-lg px-4 py-3 text-sm font-semibold transition
                {{ !$activeBranch && !$activeDepartment
                    ? 'bg-gradient-to-r from-rose-600 to-rose-900 text-white'
                    : 'text-slate-700 hover:bg-slate-200' }}"
            >
                <span>All Branches</span>
                <span>{{ $totalEmployees ?? 0 }}</span>
            </a>

            @forelse($branches as $branch)
                <div
                    class="branch-item mb-1"
                    data-branch-name="{{ strtolower($branch->name) }}"
                >
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            onclick="toggleBranch({{ $branch->id }})"
                            class="flex h-9 w-7 shrink-0 items-center justify-center text-slate-500 hover:text-rose-600"
                            aria-label="Toggle departments"
                        >
                            <span id="branch-arrow-{{ $branch->id }}">⌄</span>
                        </button>

                        <a
                            href="{{ route('hr.person.index', ['branch_id' => $branch->id]) }}"
                            class="flex min-w-0 flex-1 items-center justify-between rounded-lg px-2 py-2.5 text-sm
                            {{ $activeBranch === $branch->id && !$activeDepartment
                                ? 'bg-rose-600 font-semibold text-white'
                                : 'text-slate-700 hover:bg-slate-200' }}"
                        >
                            <span class="truncate">{{ $branch->name }}</span>
                            <span class="ml-2 text-xs opacity-75">
                                {{ $branch->employees_count }}
                            </span>
                        </a>
                    </div>

                    <div
                        id="branch-departments-{{ $branch->id }}"
                        class="ml-7 space-y-1 pb-2"
                    >
                        @forelse($branch->departments as $department)
                            <a
                                href="{{ route('hr.person.index', [
                                    'branch_id' => $branch->id,
                                    'department_id' => $department->id
                                ]) }}"
                                class="flex items-center justify-between rounded-lg px-3 py-2 text-sm
                                {{ $activeDepartment === $department->id
                                    ? 'bg-rose-100 font-semibold text-rose-700'
                                    : 'text-slate-600 hover:bg-slate-200' }}"
                            >
                                <span class="truncate">
                                    {{ $department->name }}
                                </span>
                                <span class="ml-2 text-xs opacity-70">
                                    {{ $department->employees_count }}
                                </span>
                            </a>
                        @empty
                            <p class="px-3 py-2 text-xs text-slate-400">
                                No departments
                            </p>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500">
                    No branches found.
                </div>
            @endforelse

        </div>
    </aside>

    {{-- RIGHT: EMPLOYEE DIRECTORY --}}
    <main class="person-directory flex min-w-0 w-full flex-1 flex-col overflow-hidden bg-red-50">

        <div class="person-hero person-toolbar" role="region" aria-label="Person actions">
            <div class="person-toolbar-group">
                <label class="person-sub-departments">
                    <input type="checkbox" checked onchange="document.querySelector('.person-workspace').classList.toggle('person-hide-departments', !this.checked)">
                    <span>Show Sub Department</span>
                </label>
                <span class="person-toolbar-divider" aria-hidden="true"></span>
            <div class="person-hero-actions">
                <button type="button" onclick="openPersonModal('departmentModal')"
                    class="person-action erp-primary-action">+ Add Department</button>
                <a
    href="{{ route('hr.person.create') }}"
    class="person-action erp-primary-action"
>
    + Add Employee
</a>
            </div>
            </div>
            <div class="person-toolbar-group">
                <button type="button" class="person-tool" title="Print" aria-label="Print employee directory" onclick="window.print()">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 8V3h10v5M7 17H4V8h16v9h-3M7 14h10v7H7zM17 11h.01"/></svg>
                </button>
                <button type="button" class="person-tool" title="Filter employees" aria-label="Focus employee search" onclick="document.querySelector('.person-directory-bar input[type=search]').focus()">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h18l-7 8v8l-4-2v-6z"/></svg>
                </button>
                <button type="button" class="person-tool" title="Full screen" aria-label="Toggle full screen" onclick="togglePersonFullscreen()">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5"/></svg>
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="mx-5 mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mx-5 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-semibold">Please correct the following:</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="person-directory-bar">
            <div>
                <h2 class="font-semibold text-slate-900">
                    Employee Directory
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $employees->total() }} matching employees
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('hr.person.index') }}"
                class="flex w-full min-w-0 gap-2 sm:w-auto"
            >
                @if($activeBranch)
                    <input type="hidden" name="branch_id" value="{{ $activeBranch }}">
                @endif

                @if($activeDepartment)
                    <input type="hidden" name="department_id" value="{{ $activeDepartment }}">
                @endif

                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search employee ID or name..."
                    class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-rose-500 sm:w-64"
                >

                <button
                    type="submit"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                >
                    Search
                </button>
            </form>
        </div>

       <div class="person-table-scroll min-w-0 w-full flex-1 overflow-x-auto overflow-y-auto">
            <table class="person-table w-full min-w-[760px] table-auto text-left">
                <thead class="border-b border-slate-200">
                    <tr class="text-xs font-semibold uppercase tracking-wide">
                        <th class="px-4 py-4">Employee</th>
                        <th class="px-4 py-4">Employee ID</th>
                        <th class="px-4 py-4">Branch</th>
                        <th class="px-4 py-4">Department</th>
                        <th class="px-4 py-4">Employment</th>
                        <th class="px-4 py-4">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($employees as $employee)
                        @php
                            $fullName = trim(
                                ($employee->first_name ?? '') . ' ' .
                                ($employee->last_name ?? '')
                            );

                            $fullName = $fullName ?: ($employee->name ?: 'Unnamed Employee');

                            $initials = collect(explode(' ', $fullName))
                                ->filter()
                                ->take(2)
                                ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                ->implode('');

                            $isActive = strtolower($employee->status ?? 'active') === 'active';
                        @endphp

                        
<tr class="transition hover:bg-rose-50/40">
    <td class="px-4 py-3">
        <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-200 text-xs font-bold text-slate-600" style="width:36px;height:36px;position:relative;flex-shrink:0;overflow:hidden;border-radius:50%;">
                <span>{{ $initials }}</span>
                @if($employee->profile_photo)
                    <img src="{{ route('hr.person.employees.photo', $employee) }}"
                         alt="{{ $fullName }}"
                         width="36" height="36" loading="lazy"
                         style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;"
                         onerror="this.remove()">
                @endif
            </div>

            <div class="min-w-0">
                <p class="text-[13px] font-semibold text-slate-900">
                    {{ $fullName }}
                </p>
                <p class="mt-0.5 text-[11px] text-slate-500">
                    {{ $employee->personal_email ?: 'No personal email' }}
                </p>
            </div>
        </div>
    </td>

    <td class="px-3 py-3 text-xs font-semibold text-slate-900">
        {{ $employee->employee_id }}
    </td>

    <td class="px-3 py-3 text-xs text-slate-700">
        {{ $employee->branch?->name ?? '—' }}
    </td>

    <td class="px-3 py-3 text-xs text-slate-700">
        {{ $employee->department?->name ?? '—' }}
    </td>

    <td class="px-3 py-3 text-xs text-slate-600">
        {{ $employee->employment_type }}
    </td>

    <td class="px-3 py-3">
        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold
            {{ $isActive
                ? 'bg-emerald-100 text-emerald-700'
                : (strtolower($employee->status ?? '') === 'resigned' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600') }}">
            {{ $employee->status ?: 'Active' }}
        </span>
    </td>
</tr>

                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-20 text-center">
                                <p class="font-semibold text-slate-800">
                                    No employees found
                                </p>
                                <p class="mt-2 text-sm text-slate-500">
                                    Try another branch or search term.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-5 py-4">
            {{ $employees->links() }}
        </div>

    </main>
</div>

{{-- ADD DEPARTMENT MODAL --}}
<div
    id="departmentModal"
    class="person-modal hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="departmentModalTitle"
>
    <div class="person-modal-panel w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-center justify-between">
            <h2 id="departmentModalTitle" class="text-xl font-bold text-slate-900">
                Add Department
            </h2>
            <button type="button" onclick="closePersonModal('departmentModal')" class="text-2xl text-slate-400 hover:text-slate-800">
                &times;
            </button>
        </div>

        <form method="POST" action="{{ route('hr.person.departments.store') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="newDepartmentBranch" class="mb-2 block text-sm font-semibold text-slate-700">
                    Branch *
                </label>

                <select
                    id="newDepartmentBranch"
                    name="branch_id"
                    required
                    class="w-full rounded-lg border border-slate-200 px-3 py-3 text-sm focus:border-rose-500"
                >
                    <option value="">Select branch</option>
                    @foreach($branches as $branch)
                        <option
                            value="{{ $branch->id }}"
                            @selected(old('branch_id', $activeBranch) == $branch->id)
                        >
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="newDepartmentName" class="mb-2 block text-sm font-semibold text-slate-700">
                    Department Name *
                </label>
                <input
                    id="newDepartmentName"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    maxlength="255"
                    placeholder="Enter department name"
                    class="w-full rounded-lg border border-slate-200 px-3 py-3 text-sm focus:border-rose-500"
                >
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                <button type="button" onclick="closePersonModal('departmentModal')" class="rounded-lg border px-5 py-2.5 text-sm">
                    Cancel
                </button>
                <button type="submit" class="erp-primary-action rounded-lg px-5 py-2.5 text-sm font-semibold text-white">
                    Save Department
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ADD EMPLOYEE MODAL --}}
<div
    id="employeeModal"
    class="person-modal hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="employeeModalTitle"
>
    <div class="person-modal-panel flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">

        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <h2 id="employeeModalTitle" class="text-xl font-bold text-slate-900">
                    Add New Employee
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Employee Registration · Partner IT ERP
                </p>
            </div>

            <button type="button" onclick="closePersonModal('employeeModal')" class="text-2xl text-slate-400 hover:text-slate-800">
                &times;
            </button>
        </div>

        <form
            method="POST"
            action="{{ route('hr.person.employees.store') }}"
            class="person-modal-form min-h-0 flex-1 overflow-y-auto"
        >
            @csrf

            <div class="space-y-7 p-6">

                <section>
                    <h3 class="mb-4 border-b border-slate-100 pb-2 text-sm font-bold uppercase tracking-wide text-rose-600">
                        Basic Information
                    </h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Employee ID *</label>
                            <input name="employee_id" value="{{ old('employee_id') }}" required maxlength="255" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm" placeholder="Biometric Employee ID">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Joined Date *</label>
                            <input type="date" name="joined_date" value="{{ old('joined_date', date('Y-m-d')) }}" required class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">First Name *</label>
                            <input name="first_name" value="{{ old('first_name') }}" required class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Last Name *</label>
                            <input name="last_name" value="{{ old('last_name') }}" required class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="mb-4 border-b border-slate-100 pb-2 text-sm font-bold uppercase tracking-wide text-rose-600">
                        Employment Information
                    </h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Branch *</label>
                            <select
                                id="employeeBranch"
                                name="branch_id"
                                required
                                onchange="filterEmployeeDepartments()"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm"
                            >
                                <option value="">Select branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('branch_id', $activeBranch) == $branch->id)>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Department *</label>
                            <select
                                id="employeeDepartment"
                                name="department_id"
                                required
                                data-old-value="{{ old('department_id') }}"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm"
                            >
                                <option value="">Select department</option>
                                @foreach($branches as $branch)
                                    @foreach($branch->departments as $department)
                                        <option value="{{ $department->id }}" data-branch="{{ $branch->id }}">
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium">Employment Type *</label>
                            <select name="employment_type" required class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                                <option value="">Select employment type</option>
                                @foreach(['Probation', 'Permanent', 'Contract', 'Intern'] as $type)
                                    <option value="{{ $type }}" @selected(old('employment_type') === $type)>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                <section>
                    <h3 class="mb-4 border-b border-slate-100 pb-2 text-sm font-bold uppercase tracking-wide text-rose-600">
                        Private & Contact Information
                    </h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">NIC Number</label>
                            <input name="nic_number" value="{{ old('nic_number') }}" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Date of Birth</label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Gender</label>
                            <select name="gender" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                                <option value="">Select</option>
                                @foreach(['Male', 'Female', 'Other'] as $gender)
                                    <option value="{{ $gender }}" @selected(old('gender') === $gender)>{{ $gender }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Contact Number</label>
                            <input name="contact_number" value="{{ old('contact_number') }}" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium">Personal Email</label>
                            <input type="email" name="personal_email" value="{{ old('personal_email') }}" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium">Residential Address</label>
                            <textarea name="residential_address" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">{{ old('residential_address') }}</textarea>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Emergency Contact Name</label>
                            <input name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Emergency Contact Phone</label>
                            <input name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                        </div>
                    </div>
                </section>

            </div>

            <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                <button type="button" onclick="closePersonModal('employeeModal')" class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-medium">
                    Cancel
                </button>
                <button type="submit" class="erp-primary-action rounded-lg px-6 py-2.5 text-sm font-semibold text-white">
                    Save Employee
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    async function togglePersonFullscreen() {
        const workspace = document.querySelector('.person-workspace');
        if (!document.fullscreenEnabled) return;
        try {
            if (document.fullscreenElement) {
                await document.exitFullscreen();
            } else {
                await workspace.requestFullscreen();
            }
        } catch (error) {
            console.warn('Full screen is unavailable.', error);
        }
    }

    function openPersonModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closePersonModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    function toggleBranch(id) {
        const element = document.getElementById('branch-departments-' + id);
        const arrow = document.getElementById('branch-arrow-' + id);

        if (!element) return;

        element.classList.toggle('hidden');

        if (arrow) {
            arrow.textContent = element.classList.contains('hidden') ? '›' : '⌄';
        }
    }

    function filterEmployeeDepartments() {
        const branch = document.getElementById('employeeBranch');
        const department = document.getElementById('employeeDepartment');

        if (!branch || !department) return;

        const branchId = branch.value;

        Array.from(department.options).forEach(option => {
            if (!option.value) return;

            const matches = option.dataset.branch === branchId;
            option.hidden = !matches;
            option.disabled = !matches;
        });

        const selectedOption = department.selectedOptions[0];

        if (
            selectedOption &&
            selectedOption.value &&
            selectedOption.dataset.branch !== branchId
        ) {
            department.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const search = document.getElementById('branchSearch');

        if (search) {
            search.addEventListener('input', function () {
                const term = this.value.toLowerCase().trim();

                document.querySelectorAll('.branch-item').forEach(item => {
                    const branchMatches = item.dataset.branchName.includes(term);
                    const departmentMatches = Array.from(
                        item.querySelectorAll('a')
                    ).some(link => link.textContent.toLowerCase().includes(term));

                    item.classList.toggle(
                        'hidden',
                        !branchMatches && !departmentMatches
                    );
                });
            });
        }

        filterEmployeeDepartments();

        const department = document.getElementById('employeeDepartment');

        if (department && department.dataset.oldValue) {
            department.value = department.dataset.oldValue;
        }

        @if($errors->any())
            @if(session('open_modal') === 'department')
                openPersonModal('departmentModal');
            @else
                openPersonModal('employeeModal');
            @endif
        @endif
    });
</script>

</x-layouts.app>
