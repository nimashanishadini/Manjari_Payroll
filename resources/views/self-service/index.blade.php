<x-layouts.app title="Self-Service" section="self-service">
    <div class="p-6 sm:p-8 bg-slate-50 min-h-full">
        <h1 class="text-2xl font-bold text-slate-900">My Profile</h1>
        <p class="mt-2 text-sm text-slate-500">Your personal and employment information. Contact HR to request a correction.</p>

        @if($employee)
            <section class="mt-6 bg-white border border-slate-200 rounded-2xl p-6">
                <h2 class="text-lg font-bold text-slate-900">{{ $employee->name }}</h2>
                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @foreach([
                        'Employee ID' => $employee->employee_id,
                        'Branch' => $employee->branch?->name,
                        'Department' => $employee->department?->name,
                        'Employment Type' => $employee->employment_type,
                        'Status' => $employee->status,
                        'Joined Date' => $employee->joined_date ? substr((string) $employee->joined_date, 0, 10) : null,
                        'Line Manager' => $employee->lineManager?->name,
                        'Work Email' => $employee->email,
                        'Probation End Date' => $employee->probation_end_date?->format('Y-m-d'),
                        'Confirmed Date' => $employee->confirmed_date?->format('Y-m-d'),
                    ] as $label => $value)
                        <div>
                            <dt class="text-sm text-slate-500">{{ $label }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @else
            <div class="mt-6 p-6 bg-white border border-slate-200 rounded-2xl">Your employee profile has not been linked. Please contact HR.</div>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <input type="hidden" name="_redirect_after_logout" value="1">
            <button class="erp-primary-action px-5 py-3 rounded-xl" type="submit">Log out</button>
        </form>
    </div>
</x-layouts.app>
