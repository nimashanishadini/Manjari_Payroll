{{-- <x-layouts.app title="Dashboard | HR & Payroll">
    <section class="mx-auto max-w-7xl">
        <div class="mb-8">
            <p class="text-sm font-medium uppercase tracking-wider text-red-700">HR &amp; Payroll</p>
            <h1 class="mt-2 text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-2 text-gray-600">Welcome back, {{ auth()->user()->name }}.</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Your workspace is ready</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600">
                Use the sidebar to navigate the sections available to your account.
            </p>
        </div>
    </section>
</x-layouts.app> --}}


<x-layouts.app
    title="Dashboard"
    section="dashboard"
>
    <div class="erp-dashboard">

        <h1 style="font-size:24px;font-weight:700">
            Dashboard Summary
        </h1>

        <p style="color:#777;margin-top:8px">
            HR & Payroll Management Overview
        </p>

        <div class="erp-dashboard-grid">

            <div class="erp-stat">
                <small>Total Employees</small>
                <strong>
                    {{ \App\Models\Employee::count() }}
                </strong>
            </div>

            <div class="erp-stat">
                <small>Branches</small>
                <strong>
                    {{ \App\Models\Branch::count() }}
                </strong>
            </div>

            <div class="erp-stat">
                <small>Departments</small>
                <strong>
                    {{ \App\Models\Department::count() }}
                </strong>
            </div>

            <div class="erp-stat">
                <small>Designations</small>
                <strong>
                    {{ \App\Models\Designation::count() }}
                </strong>
            </div>

        </div>

    </div>
</x-layouts.app>

