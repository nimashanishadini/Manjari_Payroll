<form id="employmentDetailsSearch" class="mt-5 flex flex-wrap items-end gap-3">
    <div>
        <label for="employmentLookupId" class="mb-2 block text-sm font-medium text-slate-700">Employee ID</label>
        <input id="employmentLookupId" type="text" required maxlength="255" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm" placeholder="Enter employee ID">
    </div>
    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">View Details</button>
</form>
<p id="employmentDetailsStatus" role="status" aria-live="polite" class="mt-4 text-sm text-slate-600"></p>
<div id="employmentDetailsResult" class="mt-5" hidden>
    <img id="employmentDetailsPhoto" alt="Employee profile photo" width="80" height="80" hidden class="mb-5 rounded-full" style="width:80px;height:80px;object-fit:cover;" onerror="this.hidden = true">
    <dl class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach([
            'employee_id' => 'Employee ID', 'name' => 'Full Name', 'first_name' => 'First Name', 'last_name' => 'Last Name',
            'branch_name' => 'Branch', 'branch_code' => 'Branch Code', 'department_name' => 'Department',
            'employment_type' => 'Employment Type', 'status' => 'Status', 'joined_date' => 'Date of Employment',
            'line_manager_name' => 'Line Manager', 'probation_end_date' => 'Probation End Date', 'confirmed_date' => 'Confirmed Date',
            'email' => 'Work Email', 'phone' => 'Phone', 'personal_email' => 'Personal Email', 'contact_number' => 'Contact Number',
            'nic_number' => 'NIC Number', 'date_of_birth' => 'Date of Birth', 'gender' => 'Gender', 'blood_group' => 'Blood Group',
            'residential_address' => 'Residential Address', 'emergency_contact_name' => 'Emergency Contact Name',
            'emergency_contact_phone' => 'Emergency Contact Phone', 'allow_self_service' => 'Self-Service Login',
            'created_at' => 'Record Created', 'updated_at' => 'Last Updated',
        ] as $field => $label)
            <div>
                <dt class="text-xs font-medium text-slate-500">{{ $label }}</dt>
                <dd data-employment-detail="{{ $field }}" class="mt-1 break-words text-sm text-slate-900" style="white-space:pre-wrap;"></dd>
            </div>
        @endforeach
    </dl>
    <h3 class="mt-7 font-semibold text-slate-900">Employment History</h3>
    <div id="employmentHistoryResult" class="mt-3 space-y-3 text-sm text-slate-700"></div>
    <form id="employmentHistoryForm" class="mt-7 rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-900">Add Employment Period</h3>
        <p class="mt-2 text-xs text-slate-500">Each period preserves its reporting manager. For a promotion, transfer or manager change, select Promotion / Transfer. History records are retained; older periods remain available.</p>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="historyAction" class="mb-2 block text-sm text-slate-700">Action</label>
                <select id="historyAction" class="w-full rounded-lg border border-slate-300 p-2.5">
                    <option value="store">Initial / Historical Period</option>
                    <option value="transfer">Promotion / Transfer / Manager Change</option>
                </select>
            </div>
            <div>
                <label for="historyBranch" class="mb-2 block text-sm text-slate-700">Branch</label>
                <select id="historyBranch" name="branch_id" required class="w-full rounded-lg border border-slate-300 p-2.5">
                    <option value="">Select branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="historyDepartment" class="mb-2 block text-sm text-slate-700">Department</label>
                <select id="historyDepartment" name="department_id" required class="w-full rounded-lg border border-slate-300 p-2.5">
                    <option value="">Select department</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" data-branch="{{ $department->branch_id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="historyDesignation" class="mb-2 block text-sm text-slate-700">Designation</label>
                <select id="historyDesignation" name="designation_id" required class="w-full rounded-lg border border-slate-300 p-2.5">
                    <option value="">Select designation</option>
                    @foreach($designations as $designation)
                        <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                    @endforeach
                </select>
                @if($designations->isEmpty())
                    <p class="mt-2 text-xs text-slate-500">Add an active designation before recording employment history.</p>
                @endif
            </div>
            <div>
                <label for="historyManager" class="mb-2 block text-sm text-slate-700">Reporting Manager</label>
                <select id="historyManager" name="manager_id" class="w-full rounded-lg border border-slate-300 p-2.5">
                    <option value="">No reporting manager</option>
                    @foreach($managers as $manager)
                        <option value="{{ $manager->id }}">{{ $manager->employee_id }} — {{ $manager->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="historyStart" class="mb-2 block text-sm text-slate-700">Start Date</label>
                <input id="historyStart" name="start_date" type="date" required max="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-300 p-2.5">
            </div>
            <div>
                <label for="historyEnd" class="mb-2 block text-sm text-slate-700">End Date (leave empty for current period)</label>
                <input id="historyEnd" name="end_date" type="date" max="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-300 p-2.5">
            </div>
            <div>
                <label for="historyReason" class="mb-2 block text-sm text-slate-700">Reason</label>
                <input id="historyReason" name="change_reason" required maxlength="255" placeholder="Initial appointment, promotion, transfer…" class="w-full rounded-lg border border-slate-300 p-2.5">
            </div>
        </div>
        <button id="saveHistoryButton" type="submit" class="mt-5 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Save Employment Period</button>
        <p id="historySaveStatus" role="status" aria-live="polite" class="mt-3 whitespace-pre-line text-sm text-slate-600"></p>
    </form>
</div>
