<x-layouts.app title="Biometric Devices" section="device">
    <section class="device-page mx-auto max-w-7xl p-6 lg:p-10">
        <x-page-header overline="Device / Biometric Onboarding" title="Biometric Devices" description="Register terminals and manage their branch, network and timezone settings.">
            <x-slot:icon><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="3"/><path d="M9 6h6M9 10h6m-4 8h2"/></svg></x-slot:icon>
            <x-slot:stats><span>{{ $totalDevices }} registered devices</span><span>{{ $activeDevices }} online (configured)</span><span>{{ $totalDevices - $activeDevices }} offline (configured)</span></x-slot:stats>
        </x-page-header>
        @if($errors->any())<div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <details id="deviceEditor" class="mb-6 rounded-xl border border-slate-200 bg-white" @if($editingDevice || $errors->any()) open @endif>
            <summary class="cursor-pointer p-5 text-sm font-semibold text-slate-800">{{ $editingDevice ? 'Edit device · '.$editingDevice->name : '＋ Register biometric device' }}</summary>
            <form method="POST" action="{{ $editingDevice ? route('hr.device.update', $editingDevice) : route('hr.device.store') }}" class="device-form border-t border-slate-100 p-5">
                @csrf @if($editingDevice) @method('PUT') @endif
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div><label for="deviceName">Device name <span>*</span></label><input id="deviceName" name="name" value="{{ old('name', $editingDevice?->name) }}" required maxlength="150" placeholder="e.g. Head Office Entrance"></div>
                    <div><label for="deviceBrand">Brand <span>*</span></label><input id="deviceBrand" name="brand" value="{{ old('brand', $editingDevice?->brand) }}" required maxlength="100" placeholder="e.g. Hikvision"></div>
                    <div><label for="deviceSerial">Serial number <span>*</span></label><input id="deviceSerial" name="serial_number" value="{{ old('serial_number', $editingDevice?->serial_number) }}" required maxlength="100" placeholder="Unique device serial number"></div>
                    <div><label for="deviceBranch">Branch <span>*</span></label><select id="deviceBranch" name="branch_id" required><option value="">Select branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" data-timezone="{{ $branch->timezone }}" @selected((string) old('branch_id', $editingDevice?->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
                    <div><label for="deviceDepartment">Department <small>(optional)</small></label><select id="deviceDepartment" name="department_id"><option value="">All departments in branch</option>@foreach($departments as $department)<option value="{{ $department->id }}" data-branch="{{ $department->branch_id }}" @selected((string) old('department_id', $editingDevice?->department_id) === (string) $department->id)>{{ $department->name }}</option>@endforeach</select></div>
                    <div><label for="deviceTimezone">Timezone <span>*</span></label><select id="deviceTimezone" name="timezone" required>@foreach($timezones as $timezone)<option value="{{ $timezone }}" @selected(old('timezone', $editingDevice?->timezone ?? 'Asia/Colombo') === $timezone)>{{ $timezone }}</option>@endforeach</select></div>
                    <div><label for="deviceIp">IP address <span>*</span></label><input id="deviceIp" name="ip_address" value="{{ old('ip_address', $editingDevice?->ip_address) }}" required maxlength="45" placeholder="192.168.1.100" autocomplete="off"></div>
                    <div><label for="devicePort">Port <span>*</span></label><input id="devicePort" name="port" type="number" min="1" max="65535" value="{{ old('port', $editingDevice?->port) }}" required placeholder="Device communication port"></div>
                    <div><label for="deviceActive">Status</label><select id="deviceActive" name="is_active"><option value="1">Online</option><option value="0" @selected((string) old('is_active', $editingDevice ? (int) $editingDevice->is_active : 1) === '0')>Offline</option></select></div>
                </div>
                <p class="mt-4 text-xs leading-5 text-slate-500">Online / Offline is a manually configured status. Actual connection and sync status will be supplied by the biometric bridge when it is connected.</p>
                <div class="mt-5 flex justify-end gap-3 border-t border-slate-100 pt-4">@if($editingDevice)<a href="{{ route('hr.device.index') }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-600">Cancel edit</a>@endif<button class="erp-primary-action rounded-lg px-5 py-2.5 text-sm font-semibold">{{ $editingDevice ? 'Save device changes' : 'Register device' }}</button></div>
            </form>
        </details>
        <form method="GET" action="{{ route('hr.device.index') }}" class="device-filters mb-5 flex flex-wrap items-center gap-3">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Name, serial, brand or IP" aria-label="Search devices">
            <select name="branch_id" aria-label="Filter branch"><option value="">All branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</select>
            <select name="active" aria-label="Filter device status"><option value="">All devices</option><option value="1" @selected(($filters['active'] ?? '') === '1')>Online</option><option value="0" @selected(($filters['active'] ?? '') === '0')>Offline</option></select>
            <button class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Filter</button>
        </form>
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="p-4">Device</th><th class="p-4">Branch / Department</th><th class="p-4">Network</th><th class="p-4">Timezone</th><th class="p-4">Status</th><th class="p-4">Sync status</th><th class="p-4">Action</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($devices as $device)
                        <tr class="align-top hover:bg-slate-50/60">
                            <td class="p-4"><p class="font-semibold text-slate-900">{{ $device->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $device->brand }} · {{ $device->serial_number }}</p></td>
                            <td class="p-4 text-slate-700">{{ $device->branch->name }}<p class="mt-1 text-xs text-slate-500">{{ $device->department?->name ?? 'All departments' }}</p></td>
                            <td class="p-4 font-mono text-xs text-slate-600">{{ $device->ip_address }}<p class="mt-1">Port {{ $device->port }}</p></td>
                            <td class="p-4 text-xs text-slate-600">{{ $device->timezone }}</td>
                            <td class="p-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $device->is_active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">{{ $device->is_active ? 'Online' : 'Offline' }}</span></td>
                            <td class="p-4"><span class="text-xs font-semibold {{ $device->status === 'Online' ? 'text-green-700' : ($device->status === 'Not Synced' ? 'text-slate-500' : 'text-red-700') }}">{{ $device->status }}</span><p class="mt-1 text-xs text-slate-500">{{ $device->last_sync_at ? $device->last_sync_at->timezone($device->timezone)->format('Y-m-d H:i').' ('.$device->timezone.')' : 'Never synced' }}</p></td>
                            <td class="p-4"><a href="{{ route('hr.device.edit', $device) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-10 text-center text-slate-500">No devices found. Register a device to get started.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $devices->links() }}</div>
    </section>
    <style>
        .device-form label{display:block;margin-bottom:8px;font-size:13px;font-weight:600;color:#475569}.device-form label span{color:#be123c}.device-form label small{font-size:12px;color:#94a3b8;font-weight:400}.device-form input,.device-form select,.device-filters input,.device-filters select{box-sizing:border-box;min-height:42px;border:1px solid #cbd5e1;border-radius:8px;background:white;padding:10px 12px;font-size:14px;color:#0f172a}.device-form input,.device-form select{width:100%}.device-form select,.device-filters select{padding-right:32px}.device-form :is(input,select):focus,.device-filters :is(input,select):focus{outline:2px solid #cbd5e1;outline-offset:1px;border-color:#64748b;box-shadow:none}.device-page input::placeholder{color:#94a3b8}@media(max-width:600px){.device-filters input,.device-filters select{width:100%}}
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const branch = document.getElementById('deviceBranch');
            const department = document.getElementById('deviceDepartment');
            const filterDepartments = () => {
                for (const option of department.options) {
                    if (!option.value) continue;
                    option.hidden = option.dataset.branch !== branch.value;
                    option.disabled = option.hidden;
                }
                if (department.selectedOptions[0]?.disabled) department.value = '';
            };
            branch.addEventListener('change', () => {
                filterDepartments();
                const timezone = branch.selectedOptions[0]?.dataset.timezone;
                if (timezone) document.getElementById('deviceTimezone').value = timezone;
            });
            filterDepartments();
        });
    </script>
</x-layouts.app>
