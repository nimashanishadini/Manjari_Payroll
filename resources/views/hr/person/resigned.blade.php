<x-layouts.app title="Resigned Employees" section="person">
    <section class="mx-auto max-w-7xl p-6 lg:p-10">
        <x-page-header
            overline="Person / Employee Management"
            title="Resigned Employees"
            :badge="$employees->total().' resigned employees'"
            description="Employment records retained after resignation."
        >
            <x-slot:icon>
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="9" cy="7" r="4" />
                    <path d="M2 21v-2a7 7 0 0 1 12-5M16 17h6m-3-3 3 3-3 3" />
                </svg>
            </x-slot:icon>
        </x-page-header>
        <form method="GET" class="mt-6 flex flex-wrap gap-2">
            <input type="search" name="search" value="{{ $search }}" maxlength="100" placeholder="Search name or employee ID" aria-label="Search resigned employees" class="w-full rounded-lg border-slate-300 text-sm sm:w-80">
            <button class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white">Search</button>
        </form>
        <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Employee</th><th class="p-4">Employee ID</th><th class="p-4">Department</th><th class="p-4">Resignation reason</th><th class="p-4">Resignation date</th><th class="p-4">Status</th></tr></thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr class="border-t border-slate-100 align-top">
                            <td class="p-4 font-medium text-slate-900">{{ $employee->name ?: trim($employee->first_name.' '.$employee->last_name) }}</td>
                            <td class="p-4 text-slate-600">{{ $employee->employee_id }}</td>
                            <td class="p-4 text-slate-600">{{ $employee->department?->name ?? '—' }}</td>
                            <td class="max-w-sm whitespace-pre-wrap break-words p-4 text-slate-600">{{ $employee->resignation_reason ?? '—' }}</td>
                            <td class="whitespace-nowrap p-4 text-slate-600">{{ $employee->resigned_on?->format('Y-m-d') ?? '—' }}</td>
                            <td class="p-4"><span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">Resigned</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-10 text-center text-slate-500">{{ $search ? 'No matching resigned employees.' : 'No resigned employees recorded.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $employees->links() }}</div>
    </section>
</x-layouts.app>
