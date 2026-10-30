
<x-layouts.app title="Dashboard | HR & Payroll">

    {{-- Dashboard styling to match the supplied screenshot --}}
    <style>
        .dashboard-shell {
            width: 100%;
            max-width: 825px;
            margin-left: auto;
            margin-right: auto;
            padding-bottom: 12px;
            color: #111827;
        }

        .dashboard-shell > * + * {
            margin-top: 16px;
        }

        .dashboard-shell a {
            text-decoration: none;
            transition: background-color .18s ease, border-color .18s ease,
                        color .18s ease, box-shadow .18s ease;
        }

        .dashboard-shell .dashboard-welcome,
        .dashboard-shell .dashboard-panel,
        .dashboard-shell .dashboard-stat,
        .dashboard-shell .dashboard-quick-link {
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .08);
            background: #fff;
        }

        .dashboard-shell .dashboard-welcome,
        .dashboard-shell .dashboard-panel {
            border-radius: 12px;
        }

        .dashboard-shell .dashboard-stat {
            border-radius: 9px;
        }

        .dashboard-shell .dashboard-quick-link {
            border-radius: 9px;
        }

        .dashboard-shell .dashboard-quick-link:hover {
            border-color: #fca5a5;
            background: #fff5f5;
        }

        .dashboard-shell .dashboard-primary-button {
            background: #ef000b;
            color: #fff;
            border-radius: 6px;
        }

        .dashboard-shell .dashboard-primary-button:hover {
            background: #c90009;
        }

        @media (max-width: 640px) {
            .dashboard-shell {
                padding-left: 12px;
                padding-right: 12px;
            }
        }
    </style>

    @php
        $totalEmployees = $totalEmployees ?? 0;
        $presentToday = $presentToday ?? 0;
        $absentToday = $absentToday ?? 0;
        $pendingApprovals = $pendingApprovals ?? 0;

        $attendanceRate = $attendanceRate ?? (
            $totalEmployees > 0
                ? round(($presentToday / $totalEmployees) * 100)
                : 0
        );

        $attendanceRate = max(0, min(100, $attendanceRate));
        $payrollStatus = $payrollStatus ?? 'Not Processed';
    @endphp

    <section class="dashboard-shell mx-auto max-w-7xl space-y-6">

        {{-- Welcome Section --}}
        <div class="dashboard-welcome flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-white p-6 shadow-sm border border-gray-200">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">
                    Welcome back, {{ auth()->user()->name ?? 'Admin' }}!
                </h2>
                <p class="mt-2 text-sm text-gray-500">
                    Here is your HR management summary for today.
                </p>
            </div>
            <a href="{{ route('hr.employee-management.index') }}"
               class="dashboard-primary-button inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold shadow-sm">
                + Manage Employees
            </a>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['label' => 'Total Employees', 'value' => $totalEmployees, 'description' => 'Registered employees', 'icon' => '♟', 'color' => 'bg-red-50 text-red-500'],
                ['label' => 'Present Today', 'value' => $presentToday, 'description' => 'Attendance recorded today', 'icon' => '✓', 'color' => 'bg-green-50 text-green-500'],
                ['label' => 'Absent Today', 'value' => $absentToday, 'description' => 'Employees not present', 'icon' => '◷', 'color' => 'bg-orange-50 text-orange-500'],
                ['label' => 'Pending Approvals', 'value' => $pendingApprovals, 'description' => 'Requests awaiting review', 'icon' => '▤', 'color' => 'bg-blue-50 text-blue-500'],
            ] as $stat)
                <div class="dashboard-stat min-w-0 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-xs font-medium text-gray-500">{{ $stat['label'] }}</p>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ $stat['color'] }}" aria-hidden="true">
                            {{ $stat['icon'] }}
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ $stat['value'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $stat['description'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <section class="dashboard-panel p-5" aria-labelledby="attendance-overview-title">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 id="attendance-overview-title" class="text-base font-bold text-gray-900">Attendance Overview</h3>
                        <p class="mt-1 text-xs text-gray-500">Today's employee attendance summary</p>
                    </div>
                    <a href="{{ route('hr.shifts-attendance.index') }}" class="text-xs font-semibold text-red-600 hover:text-red-700">
                        View Attendance →
                    </a>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-center gap-8">
                    <div class="flex h-28 w-28 items-center justify-center rounded-full"
                         style="background: conic-gradient(#ef000b {{ $attendanceRate * 3.6 }}deg, #e5e7eb 0deg);">
                        <div class="flex h-20 w-20 flex-col items-center justify-center rounded-full bg-white">
                            <span class="text-xl font-bold text-gray-900">{{ $attendanceRate }}%</span>
                            <span class="text-[10px] text-gray-500">Attendance Rate</span>
                        </div>
                    </div>
                    <dl class="space-y-3 text-xs">
                        <div>
                            <dt class="flex items-center gap-2 text-gray-500"><span class="h-2 w-2 rounded-full bg-green-500"></span>Present</dt>
                            <dd class="ml-4 font-semibold text-gray-900">{{ $presentToday }}</dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-2 text-gray-500"><span class="h-2 w-2 rounded-full bg-red-500"></span>Absent</dt>
                            <dd class="ml-4 font-semibold text-gray-900">{{ $absentToday }}</dd>
                        </div>
                        <div>
                            <dt class="flex items-center gap-2 text-gray-500"><span class="h-2 w-2 rounded-full bg-gray-300"></span>Total Employees</dt>
                            <dd class="ml-4 font-semibold text-gray-900">{{ $totalEmployees }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="dashboard-panel p-5" aria-labelledby="payroll-status-title">
                <div>
                    <h3 id="payroll-status-title" class="text-base font-bold text-gray-900">Payroll Status</h3>
                    <p class="mt-1 text-xs text-gray-500">Current payroll processing status</p>
                </div>
                <div class="mt-4 flex items-center gap-3 rounded-lg bg-gray-50 p-4">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 font-bold text-blue-600" aria-hidden="true">$</span>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Payroll Processing</p>
                        <p class="mt-1 text-xs text-gray-500">Current payroll cycle</p>
                        <span class="mt-1 inline-flex rounded-full bg-orange-100 px-2 py-1 text-[10px] font-medium text-orange-700">{{ $payrollStatus }}</span>
                    </div>
                </div>
                <p class="mt-4 text-xs leading-5 text-gray-500">Check payroll records and payslips to monitor the current payroll process.</p>
                <a href="{{ route('hr.payroll.index') }}" class="dashboard-primary-button mt-4 inline-flex px-4 py-2 text-xs font-semibold">
                    Open Payroll →
                </a>
            </section>
        </div>

        <section class="dashboard-panel p-5" aria-labelledby="quick-access-title">
            <div>
                <h3 id="quick-access-title" class="text-base font-bold text-gray-900">Quick Access</h3>
                <p class="mt-1 text-xs text-gray-500">Navigate to frequently used HR modules</p>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['title' => 'Employees', 'description' => 'View and manage employee information.', 'icon' => '♟', 'route' => 'hr.employee-management.index'],
                    ['title' => 'Attendance', 'description' => 'Review daily attendance records.', 'icon' => '◷', 'route' => 'hr.shifts-attendance.index'],
                    ['title' => 'Leave Requests', 'description' => 'Review leave requests and approvals.', 'icon' => '▤', 'route' => 'hr.leaves.index'],
                    ['title' => 'Reports', 'description' => 'Access HR and attendance reports.', 'icon' => '▥', 'route' => 'hr.reports.index'],
                ] as $item)
                    <a href="{{ route($item['route']) }}" class="dashboard-quick-link block p-4">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-500" aria-hidden="true">
                            {{ $item['icon'] }}
                        </span>
                        <span class="mt-3 block text-sm font-semibold text-gray-900">{{ $item['title'] }}</span>
                        <span class="mt-1 block text-xs leading-5 text-gray-500">{{ $item['description'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        <x-page-footer />
    </section>
</x-layouts.app>
