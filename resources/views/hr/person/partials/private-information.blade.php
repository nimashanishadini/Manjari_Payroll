<div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
        <label for="blood_group" class="mb-2 block text-sm font-medium text-slate-700">Blood Group</label>
        <select id="blood_group" name="blood_group" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
            <option value="">Select blood group</option>
            @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $group)
                <option value="{{ $group }}">{{ $group }}</option>
            @endforeach
        </select>
    </div>
    @foreach(['nic_number' => ['NIC Number', 'text'], 'date_of_birth' => ['Date of Birth', 'date'], 'contact_number' => ['Contact Number', 'tel'], 'personal_email' => ['Personal Email', 'email'], 'emergency_contact_name' => ['Emergency Contact Name', 'text'], 'emergency_contact_phone' => ['Emergency Contact Phone', 'tel']] as $field => [$label, $type])
        <div>
            <label for="{{ $field }}" class="mb-2 block text-sm font-medium text-slate-700">{{ $label }}</label>
            <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
        </div>
    @endforeach
    <div>
        <label for="gender" class="mb-2 block text-sm font-medium text-slate-700">Gender</label>
        <select id="gender" name="gender" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
            <option value="">Select gender</option>
            @foreach(['Male', 'Female', 'Other'] as $gender)
                <option value="{{ $gender }}">{{ $gender }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="residential_address" class="mb-2 block text-sm font-medium text-slate-700">Residential Address</label>
        <textarea id="residential_address" name="residential_address" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm"></textarea>
    </div>
</div>

@if(auth()->user()?->canManageEmployeeBankDetails())
    <fieldset class="mt-8 rounded-xl border border-slate-200 p-5">
        <legend class="px-2 font-semibold text-slate-900">Bank Details</legend>
        <p class="mb-4 text-sm text-slate-500">Enter your login password to load saved bank details or save changes.</p>
        <div class="grid gap-5 sm:grid-cols-2">
            @foreach(['bank_name' => 'Bank Name', 'bank_branch' => 'Bank Branch', 'bank_account_name' => 'Account Holder Name', 'bank_account_number' => 'Account Number'] as $field => $label)
                <div>
                    <label for="{{ $field }}" class="mb-2 block text-sm font-medium text-slate-700">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'bank_account_number' ? 'password' : 'text' }}" autocomplete="off"
                           @if($field === 'bank_account_number') inputmode="numeric" maxlength="34" @else maxlength="{{ $field === 'bank_account_name' ? 255 : 150 }}" @endif
                           class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
                </div>
            @endforeach
            <div>
                <label for="bank_password" class="mb-2 block text-sm font-medium text-slate-700">Your Login Password</label>
                <input id="bank_password" name="bank_password" type="password" autocomplete="current-password" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">
            </div>
            <div class="flex items-center">
                <button type="button" id="loadBankDetails" onclick="loadEmployeeBankDetails()" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Load Bank Details</button>
            </div>
        </div>
        <label class="mt-5 flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" id="update_bank" name="update_bank" value="1" class="accent-rose-600">
            Save bank details with this section
        </label>
        <p id="bankDetailsStatus" role="status" aria-live="polite" class="mt-3 text-sm text-slate-600"></p>
    </fieldset>
@endif
