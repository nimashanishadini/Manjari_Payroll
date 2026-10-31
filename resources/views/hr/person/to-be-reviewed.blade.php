<x-layouts.app title="To Be Reviewed" section="person">
    <section class="mx-auto max-w-7xl p-6 lg:p-10">
        <x-page-header overline="Person / Biometric Onboarding" title="Employees To Be Reviewed" description="Review device enrollments, complete employee profiles, or ignore unwanted records." :badge="$pendingCount.' pending'">
            <x-slot:icon><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2m-6 9 2 2 4-4"/></svg></x-slot:icon>
        </x-page-header>
        @if($errors->any())<div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <details class="mb-6 rounded-xl border border-slate-200 bg-white p-5" @if(old('raw_enrollment_id')) open @endif>
            <summary class="cursor-pointer text-sm font-semibold text-slate-700">＋ Add sample enrollment (testing)</summary>
            <p class="mt-2 text-xs leading-5 text-slate-500">Use this to test HR review before connecting the C# bridge. This entry does not enroll anyone on the physical device.</p>
            <form method="POST" action="{{ route('hr.person.enrollments.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @csrf
                <div><label for="stagingDevice" class="mb-2 block text-xs font-medium text-slate-600">Device</label><select id="stagingDevice" name="device_id" required class="staging-control"><option value="">Select device</option>@foreach($devices as $device)<option value="{{ $device->id }}" @selected((string) old('device_id') === (string) $device->id)>{{ $device->name }} · {{ $device->branch->name }}</option>@endforeach</select></div>
                <div><label for="stagingEnrollment" class="mb-2 block text-xs font-medium text-slate-600">Enrollment ID</label><input id="stagingEnrollment" name="raw_enrollment_id" value="{{ old('raw_enrollment_id') }}" required maxlength="255" pattern="[A-Za-z0-9_-]+" placeholder="e.g. EMP025" class="staging-control"></div>
                <div><label for="stagingName" class="mb-2 block text-xs font-medium text-slate-600">Name entered on device (optional)</label><input id="stagingName" name="raw_name" value="{{ old('raw_name') }}" maxlength="255" placeholder="e.g. Kamal" class="staging-control"></div>
                <div class="sm:col-span-2 lg:col-span-3"><button class="erp-primary-action rounded-lg px-5 py-2.5 text-sm font-semibold">Add for HR review</button></div>
            </form>
        </details>
        <form method="GET" class="mb-5 flex flex-wrap gap-3">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Search enrollment ID or name" aria-label="Search enrollments" class="staging-control sm:max-w-xs">
            <select name="status" aria-label="Enrollment review status" class="staging-control sm:max-w-xs">@foreach(['Pending HR Review', 'Promoted', 'Ignored', 'All'] as $status)<option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $status }}</option>@endforeach</select>
            <button class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Filter</button>
        </form>
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="p-4">Enrollment ID</th><th class="p-4">Name</th><th class="p-4">Device / Branch</th><th class="p-4">Status</th><th class="p-4">Fetched</th><th class="p-4">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($enrollments as $enrollment)
                        @php($conflict = in_array($enrollment->raw_enrollment_id, $existingIds, true))
                        <tr class="align-top hover:bg-slate-50/60">
                            <td class="p-4 font-semibold text-slate-900">{{ $enrollment->raw_enrollment_id }}<p class="mt-1 text-xs font-normal text-slate-400">{{ $enrollment->source }} entry</p></td>
                            <td class="p-4 text-slate-700">{{ $enrollment->raw_name ?: 'Name not provided' }}</td>
                            <td class="p-4 text-slate-700">{{ $enrollment->device->name }}<p class="mt-1 text-xs text-slate-500">{{ $enrollment->branch->name }}</p></td>
                            <td class="p-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $enrollment->status === 'Promoted' ? 'bg-green-50 text-green-700' : ($enrollment->status === 'Ignored' ? 'bg-slate-100 text-slate-500' : 'bg-amber-50 text-amber-800') }}">{{ $enrollment->status }}</span>@if($enrollment->ignore_reason)<p class="mt-2 max-w-xs break-words text-xs text-slate-500">{{ $enrollment->ignore_reason }}</p>@endif</td>
                            <td class="whitespace-nowrap p-4 text-xs text-slate-500">{{ $enrollment->fetched_at->timezone('Asia/Colombo')->format('Y-m-d H:i') }}</td>
                            <td class="p-4">
                                @if($enrollment->status === 'Pending HR Review')
                                    <div class="flex items-center gap-2">
                                        @if(!$conflict && $enrollment->device->is_active)
                                            <a href="{{ route('hr.person.create', ['staging' => $enrollment->id]) }}" class="erp-primary-action rounded-lg px-3 py-2 text-xs font-semibold">Review</a>
                                        @endif
                                        <button type="button" data-ignore-enrollment="{{ $enrollment->id }}" data-ignore-url="{{ route('hr.person.enrollments.ignore', $enrollment) }}" data-ignore-name="{{ $enrollment->raw_name ?: 'Name not provided' }} · {{ $enrollment->raw_enrollment_id }}" onclick="openIgnoreEnrollment(this)" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600">Ignore</button>
                                    </div>
                                    @if($conflict)<p class="mt-2 max-w-xs text-xs text-red-700">Employee ID already exists. Duplicate promotion is blocked.</p>@elseif(!$enrollment->device->is_active)<p class="mt-2 text-xs text-slate-500">Enable the device before review.</p>@endif
                                @else
                                    <span class="text-xs text-slate-500">{{ $enrollment->reviewed_at?->timezone('Asia/Colombo')->format('Y-m-d H:i') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-10 text-center text-slate-500">No enrollments found for this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $enrollments->links() }}</div>
    </section>
    <dialog id="ignoreEnrollmentDialog" aria-labelledby="ignoreEnrollmentTitle" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl backdrop:bg-slate-900/40" style="position:fixed;inset:0;margin:auto;width:min(480px,calc(100vw - 32px));max-height:calc(100dvh - 32px);overflow:auto;">
        <form id="ignoreEnrollmentForm" method="POST">
            @csrf <input type="hidden" id="ignoredEnrollmentId" name="ignore_id">
            <h2 id="ignoreEnrollmentTitle" class="text-lg font-bold text-slate-900">Ignore enrollment</h2><p id="ignoredEnrollmentName" class="mt-2 text-sm text-slate-500"></p>
            <label for="ignoreEnrollmentReason" class="mb-2 mt-4 block text-sm font-medium text-slate-700">Reason</label><textarea id="ignoreEnrollmentReason" name="ignore_reason" required maxlength="1000" rows="3" class="staging-control">{{ old('ignore_reason') }}</textarea>
            <div class="mt-5 flex justify-end gap-2"><button type="button" onclick="document.getElementById('ignoreEnrollmentDialog').close()" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600">Cancel</button><button class="erp-primary-action rounded-lg px-4 py-2 text-sm font-semibold">Confirm ignore</button></div>
        </form>
    </dialog>
    <style>.staging-control{display:block;box-sizing:border-box;width:100%;min-height:42px;border:1px solid #cbd5e1;border-radius:8px;padding:10px 12px;background:white;font-size:14px;color:#0f172a}select.staging-control{padding-right:32px}.staging-control:focus{outline:2px solid #cbd5e1;outline-offset:1px;border-color:#64748b;box-shadow:none}</style>
    <script>
        function openIgnoreEnrollment(button) {
            document.getElementById('ignoreEnrollmentForm').action = button.dataset.ignoreUrl;
            document.getElementById('ignoredEnrollmentId').value = button.dataset.ignoreEnrollment;
            document.getElementById('ignoredEnrollmentName').textContent = button.dataset.ignoreName;
            document.getElementById('ignoreEnrollmentDialog').showModal();
        }
        document.addEventListener('DOMContentLoaded', () => {
            const id = @json(old('ignore_id'));
            const button = Array.from(document.querySelectorAll('[data-ignore-enrollment]')).find(button => button.dataset.ignoreEnrollment === String(id));
            if (id && button) openIgnoreEnrollment(button);
        });
    </script>
</x-layouts.app>
