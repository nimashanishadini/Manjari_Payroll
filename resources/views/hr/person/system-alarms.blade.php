<x-layouts.app title="Document Alarms" section="person">
    <section class="mx-auto max-w-6xl p-6 lg:p-10">
        <x-page-header overline="Person / System Alarms" title="Document Alarms" description="Expired documents and copies due for renewal. Dates are checked when this page opens and by the scheduled expiry check.">
            <x-slot:icon>
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4M12 6v4m0 3h.01" /></svg>
            </x-slot:icon>
        </x-page-header>
        <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-4">Employee</th><th class="p-4">Document</th><th class="p-4">Expiry date</th><th class="p-4">Status</th><th class="p-4">Action</th></tr></thead>
                <tbody>
                    @forelse($alarms as $alarm)
                        <tr class="border-t border-slate-100">
                            <td class="p-4"><p class="font-semibold">{{ $alarm->document->employee?->name ?: trim(($alarm->document->employee?->first_name ?? '').' '.($alarm->document->employee?->last_name ?? '')) }}</p><p class="text-xs text-slate-500">{{ $alarm->document->employee?->employee_id }}</p></td>
                            <td class="p-4">{{ $alarm->document->documentType->name }}<p class="text-xs text-slate-500">{{ $alarm->document->name }}</p></td>
                            <td class="p-4">{{ $alarm->due_on->format('Y-m-d') }}</td>
                            <td class="p-4"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $alarm->status === 'expired' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-800' }}">{{ $alarm->status === 'expired' ? 'Expired' : 'Expiring soon' }}</span></td>
                            <td class="p-4"><a class="font-medium text-rose-700 underline" href="{{ route('hr.person.create', ['lookup' => $alarm->document->employee?->employee_id]) }}">Review employee</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-10 text-center text-slate-500">No document expiry alarms.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $alarms->links() }}</div>
    </section>
</x-layouts.app>
