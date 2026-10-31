@if($stagingEnrollment)
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <h3 class="text-sm font-semibold text-slate-900">Biometric enrollment review</h3>
        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $stagingEnrollment->device->name }} · {{ $stagingEnrollment->branch->name }}. The enrollment ID and source branch are retained. Complete the profile to promote this record.</p>
    </div>
    <div class="grid gap-2 sm:grid-cols-[160px_minmax(0,1fr)] sm:items-center">
        <label for="promotionDesignation" class="text-sm font-medium text-slate-700">Designation *</label>
        <select id="promotionDesignation" name="designation_id" required class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm"><option value="">Select designation</option>@foreach($designations->where('is_active', true) as $designation)<option value="{{ $designation->id }}">{{ $designation->name }}</option>@endforeach</select>
    </div>
    <div class="grid gap-2 sm:grid-cols-[160px_minmax(0,1fr)] sm:items-center">
        <label for="promotionManager" class="text-sm font-medium text-slate-700">Reporting manager</label>
        <select id="promotionManager" name="manager_id" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm"><option value="">No reporting manager</option>@foreach($managers as $manager)<option value="{{ $manager->id }}">{{ $manager->name ?: trim($manager->first_name.' '.$manager->last_name) }} · {{ $manager->employee_id }}</option>@endforeach</select>
    </div>
    @foreach(['nic_number' => 'NIC Number', 'email' => 'Work Email', 'phone' => 'Phone'] as $field => $label)
        <div class="grid gap-2 sm:grid-cols-[160px_minmax(0,1fr)] sm:items-center"><label for="promotion-{{ $field }}" class="text-sm font-medium text-slate-700">{{ $label }}</label><input id="promotion-{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" maxlength="{{ $field === 'nic_number' ? 50 : ($field === 'email' ? 255 : 30) }}" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm"></div>
    @endforeach
    <fieldset class="rounded-xl border border-slate-200 p-4">
        <legend class="px-2 text-sm font-semibold text-slate-800">Optional employee login</legend>
        <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" id="promotionCreateLogin" name="create_login" value="1" onchange="togglePromotionLogin()">Create a self-service login account</label>
        <p class="mt-2 text-xs text-slate-500">The account receives the Employee role. Share the initial password with the employee securely.</p>
        <div id="promotionLoginFields" hidden class="mt-4 space-y-3">
            <div><label for="promotionLoginEmail" class="mb-1 block text-xs font-medium text-slate-600">Login email</label><input id="promotionLoginEmail" name="login_email" type="email" maxlength="255" autocomplete="off" disabled class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label for="promotionLoginPassword" class="mb-1 block text-xs font-medium text-slate-600">Initial password</label><input id="promotionLoginPassword" name="login_password" type="password" minlength="8" autocomplete="new-password" disabled class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label for="promotionLoginConfirmation" class="mb-1 block text-xs font-medium text-slate-600">Confirm password</label><input id="promotionLoginConfirmation" name="login_password_confirmation" type="password" minlength="8" autocomplete="new-password" disabled class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
        </div>
    </fieldset>
@endif
