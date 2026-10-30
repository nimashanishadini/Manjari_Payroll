<x-layouts.app title="Position Management" section="person">
    <section class="mx-auto max-w-7xl p-6 lg:p-10">
        <x-page-header overline="Person / Organization" title="Position Management" description="Manage job titles and keep every employee's career history connected.">
            <x-slot:icon><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V3h8v4M3 12c6 4 12 4 18 0M12 12v4"/></svg></x-slot:icon>
            <x-slot:stats><span>{{ $totalPositions }} total designations</span><span>{{ $activePositions }} active</span><span>{{ $totalPositions - $activePositions }} inactive</span></x-slot:stats>
        </x-page-header>
        @if($errors->any())<div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        @if(auth()->user()->canManageEmploymentStructure())
            <details class="mb-6 rounded-xl border border-slate-200 bg-white p-5" @if(old('editor') === 'create') open @endif>
                <summary class="cursor-pointer text-sm font-semibold text-slate-800">＋ Add designation</summary>
                <form method="POST" action="{{ route('hr.person.positions.store') }}" class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf <input type="hidden" name="editor" value="create">
                    <div class="min-w-0 flex-1"><label for="newDesignationName" class="mb-2 block text-xs font-medium text-slate-600">Designation name</label><input id="newDesignationName" name="name" required maxlength="255" value="{{ old('editor') === 'create' ? old('name') : '' }}" placeholder="e.g. HR Executive" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
                    <div><label for="newDesignationStatus" class="mb-2 block text-xs font-medium text-slate-600">Status</label><select id="newDesignationStatus" name="is_active" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="1">Active</option><option value="0" @selected(old('editor') === 'create' && old('is_active') === '0')>Inactive</option></select></div>
                    <button class="erp-primary-action rounded-lg px-4 py-2 text-sm font-semibold">Create designation</button>
                </form>
            </details>
        @endif
        <form method="GET" class="mb-5 flex flex-wrap gap-3">
            <input name="search" type="search" value="{{ $search }}" maxlength="100" placeholder="Search designations" aria-label="Search designations" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <select name="status" aria-label="Designation status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">All statuses</option><option value="active" @selected($selectedStatus === 'active')>Active</option><option value="inactive" @selected($selectedStatus === 'inactive')>Inactive</option></select>
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filter</button>
        </form>
        <p class="mb-4 text-xs leading-5 text-slate-500">Inactive designations remain in employment history and cannot be selected for new appointments. Manage employee assignments under Add Person → Employment Details.</p>
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full min-w-[680px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-4">Designation</th><th class="px-5 py-4">Status</th><th class="px-5 py-4 text-center">Current periods</th><th class="px-5 py-4 text-center">History periods</th>@if(auth()->user()->canManageEmploymentStructure())<th class="px-5 py-4">Actions</th>@endif</tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($positions as $position)
                        <tr class="align-top transition hover:bg-slate-50/70">
                            <td class="px-5 py-4 font-semibold text-slate-900">{{ $position->name }}</td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $position->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">{{ $position->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="px-5 py-4 text-center text-slate-600">{{ $position->current_periods_count }}</td>
                            <td class="px-5 py-4 text-center text-slate-600">{{ $position->employment_histories_count }}</td>
                            @if(auth()->user()->canManageEmploymentStructure())
                                <td class="px-5 py-4">
                                    <details @if(old('editor') == (string) $position->id) open @endif>
                                        <summary class="cursor-pointer text-xs font-semibold text-slate-700">Edit designation / status</summary>
                            <form method="POST" action="{{ route('hr.person.positions.update', $position) }}" class="mt-3 min-w-56 space-y-3">
                                @csrf @method('PUT') <input type="hidden" name="editor" value="{{ $position->id }}">
                                <label class="block text-xs text-slate-600">Name<input name="name" value="{{ old('editor') == (string) $position->id ? old('name') : $position->name }}" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></label>
                                <label class="block text-xs text-slate-600">Status<select name="is_active" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="1" @selected((old('editor') == (string) $position->id ? old('is_active') : $position->is_active) == 1)>Active</option><option value="0" @selected((old('editor') == (string) $position->id ? old('is_active') : $position->is_active) == 0)>Inactive</option></select></label>
                                <button class="erp-primary-action rounded-lg px-4 py-2 text-xs font-semibold">Save changes</button>
                            </form>
                                    </details>
                                    <form method="POST" action="{{ route('hr.person.positions.destroy', $position) }}" class="mt-3" onsubmit="return confirm('Delete this designation? This cannot be undone.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" aria-label="Delete {{ $position->name }}" title="{{ $position->employment_histories_count ? 'Used in employment history — mark as Inactive instead' : 'Delete designation' }}" @disabled($position->employment_histories_count > 0) class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:text-slate-400">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/></svg>
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ auth()->user()->canManageEmploymentStructure() ? 5 : 4 }}" class="p-10 text-center text-sm text-slate-500">No designations found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $positions->links() }}</div>
    </section>
</x-layouts.app>
