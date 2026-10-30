<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Services\EmployeeDocumentService;
use Illuminate\Http\Request;

class SystemAlarmController extends Controller
{
    public function index(Request $request, EmployeeDocumentService $documents)
    {
        abort_unless($request->user()->canManageEmployeeDocuments(), 403);

        return to_route('dashboard', ['notifications' => 1]);
    }
}
