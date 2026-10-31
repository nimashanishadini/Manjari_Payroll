<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SelfServiceController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()->role === 'employee', 403);

        // Always resolve ownership from the session, never a supplied employee ID.
        $employee = Employee::with(['branch', 'department', 'lineManager'])
            ->where('user_id', $request->user()->id)->first();

        return view('self-service.index', compact('employee'));
    }
}
