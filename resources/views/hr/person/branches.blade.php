<x-layouts.app title="Branch Management" section="person">
    <section class="mx-auto max-w-7xl p-6 lg:p-10">
        <x-page-header overline="Person / Organization" title="Branch Management" description="Manage company locations and their local timezones.">
            <x-slot:stats><span>{{ $totalBranches }} branches</span></x-slot:stats>
        </x-page-header>
        @if($errors->any())<div role="alert" class="my-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <details class="my-6 rounded-xl border border-slate-200 bg-white p-5" @if(old('editor') === 'create') open @endif>
            <summary class="cursor-pointer text-sm font-semibold text-slate-800">＋ Add branch</summary>
            <form method="POST" action="{{ route('hr.branches.store') }}" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @csrf <input type="hidden" name="editor" value="create">
                <label class="text-sm font-medium text-slate-700">Branch code <span class="text-red-600">*</span><input name="branch_code" required maxlength="100" pattern="[A-Za-z0-9_-]+" placeholder="e.g. CMB_HQ" value="{{ old('editor') === 'create' ? old('branch_code') : '' }}" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"><span class="mt-1 block text-xs text-slate-500">Unique permanent code. Letters, numbers, underscores or hyphens.</span></label>
                <label class="text-sm font-medium text-slate-700">Branch name <span class="text-red-600">*</span><input name="name" required maxlength="255" placeholder="e.g. Colombo Head Office" value="{{ old('editor') === 'create' ? old('name') : '' }}" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></label>
                <label class="text-sm font-medium text-slate-700">Timezone <span class="text-red-600">*</span><select name="timezone" required class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">@foreach($timezones as $timezone)<option value="{{ $timezone }}" @selected((old('editor') === 'create' ? old('timezone', 'Asia/Colombo') : 'Asia/Colombo') === $timezone)>{{ $timezone }}</option>@endforeach</select></label>
                <div class="sm:col-span-2 lg:col-span-3"><button class="erp-primary-action rounded-lg px-5 py-2.5 text-sm font-semibold">Add branch</button></div>
            </form>
        </details>
        <form method="GET" class="mb-5 flex flex-wrap gap-3"><input type="search" name="search" value="{{ $search }}" maxlength="100" placeholder="Search branch name or code" aria-label="Search branches" class="w-72 max-w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"><button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Search</button>@if($search !== '')<a href="{{ route('hr.organization-structure.index') }}" class="px-3 py-2 text-sm text-slate-600">Clear</a>@endif</form>
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full min-w-[800px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-4">Branch</th><th class="px-5 py-4">Timezone</th><th class="px-5 py-4">Departments</th><th class="px-5 py-4">Employees</th><th class="px-5 py-4">Devices</th><th class="px-5 py-4">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($branches as $branch)
                        <tr class="align-top hover:bg-slate-50/70">
                            <td class="px-5 py-4"><div class="font-semibold text-slate-900">{{ $branch->name }}</div><div class="mt-1 text-xs text-slate-500">{{ $branch->branch_code }}</div></td>
                            <td class="px-5 py-4 text-slate-600">{{ $branch->timezone }}</td>
                            <td class="px-5 py-4">{{ $branch->departments_count }}</td><td class="px-5 py-4">{{ $branch->employees_count }}</td><td class="px-5 py-4">{{ $branch->biometric_devices_count }}</td>
                            <td class="px-5 py-4">
                                <details @if(old('editor') == (string) $branch->id) open @endif><summary class="cursor-pointer font-semibold text-slate-700">Edit branch</summary>
                                    <form method="POST" action="{{ route('hr.branches.update', $branch) }}" class="mt-4 min-w-64 space-y-3">
                                        @csrf @method('PUT') <input type="hidden" name="editor" value="{{ $branch->id }}">
                                        <p class="text-xs text-slate-500">Code: {{ $branch->branch_code }} (permanent)</p>
                                        <label class="block text-sm text-slate-700">Branch name<input name="name" required maxlength="255" value="{{ old('editor') == (string) $branch->id ? old('name') : $branch->name }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
                                        <label class="block text-sm text-slate-700">Timezone<select name="timezone" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5">@foreach($timezones as $timezone)<option value="{{ $timezone }}" @selected((old('editor') == (string) $branch->id ? old('timezone') : $branch->timezone) === $timezone)>{{ $timezone }}</option>@endforeach</select></label>
                                        <button class="erp-primary-action rounded-lg px-4 py-2 text-sm font-semibold">Save changes</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('hr.branches.destroy', $branch) }}" class="mt-3" onsubmit="return confirm('Delete this unused branch? This cannot be undone.');">@csrf @method('DELETE')<button @disabled($branch->departments_count || $branch->employees_count || $branch->biometric_devices_count) class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-600 disabled:cursor-not-allowed disabled:opacity-40" title="Only unused branches can be deleted">Delete unused branch</button></form>
                            </td>
                        </tr>
                    @empty<tr><td colspan="6" class="p-10 text-center text-slate-500">No branches found.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <p class="mt-4 text-xs text-slate-500">Branch codes stay fixed to protect attendance and historical records. Employee counts exclude deleted profiles; deletion also checks retained history.</p>
        <div class="mt-5">{{ $branches->links() }}</div>
    </section>
</x-layouts.app>
