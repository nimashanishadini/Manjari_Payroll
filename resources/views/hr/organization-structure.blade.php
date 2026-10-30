<x-layouts.app title="Organization Structure | HR & Payroll">
    <section class="mx-auto max-w-7xl">
        <x-page-header
            overline="HR & Payroll"
            title="Organization Structure "
            description="View the branches, departments, and designations in your organization."
        />

        <div class="mb-8 grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['label' => 'Branches', 'value' => $branches->count()],
                ['label' => 'Departments', 'value' => $departmentCount],
                ['label' => 'Designations', 'value' => $designationCount],
            ] as $stat)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">{{ $stat['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ $stat['value'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Branches and departments</h2>

            @forelse ($branches as $branch)
                <div class="mt-5 rounded-xl border border-gray-200 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $branch->name }}</h3>
                            <p class="mt-1 text-sm text-gray-500">Branch code: {{ $branch->branch_code }}</p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                            {{ $branch->employees_count }} {{ \Illuminate\Support\Str::plural('employee', $branch->employees_count) }}
                        </span>
                    </div>

                    @if ($branch->departments->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($branch->departments as $department)
                                <span class="rounded-lg bg-red-50 px-3 py-1.5 text-sm text-red-800">
                                    {{ $department->name }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-4 text-sm text-gray-500">No departments have been added to this branch.</p>
                    @endif
                </div>
            @empty
                <p class="mt-4 rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                    No branches have been added yet.
                </p>
            @endforelse
        </div>

    </section>
</x-layouts.app>
