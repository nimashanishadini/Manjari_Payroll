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
            'current_designation' => 'Current Designation', 'employment_type' => 'Employment Type', 'status' => 'Status', 'joined_date' => 'Date of Employment',
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
    <section class="mt-7 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:p-5" aria-labelledby="employeeCertificatesHeading">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 id="employeeCertificatesHeading" class="font-semibold text-slate-900">Certificates</h3>
                <p class="mt-1 text-xs text-slate-500">Submitted certificates and supporting documents.</p>
            </div>
            <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-500">View only</span>
        </div>
        <p id="employeeCertificatesSummary" class="mt-3 text-xs font-medium text-slate-600" role="status"></p>
        <div id="employeeCertificatesResult" class="mt-3 grid gap-3 sm:grid-cols-2"></div>
    </section>
    @include('hr.person.partials.employment-period-form')
    <h3 class="mt-7 font-semibold text-slate-900">Employment History</h3>
    <div id="employmentHistoryResult" class="mt-3 space-y-3 text-sm text-slate-700"></div>
</div>
