<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Total employees excluding soft-deleted records
        $totalEmployees = Employee::count();

        // Status values are currently empty, so do not assume
        // that employees are Active until the data is populated.
        $activeEmployees = Employee::where('status', 'Active')->count();

        // Attendance and leave tables have not been created yet.
        $presentToday = null;
        $absentToday = null;
        $pendingApprovals = null;

        // Payroll status will be connected when payroll data is checked.
        $payrollStatus = 'Not Connected';

        $attendanceRate = null;

        return view('dashboard', compact(
            'totalEmployees',
            'activeEmployees',
            'presentToday',
            'absentToday',
            'pendingApprovals',
            'payrollStatus',
            'attendanceRate'
        ));
    }
}