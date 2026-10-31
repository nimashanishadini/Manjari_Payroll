<?php
use App\Http\Controllers\Hr\BranchController;
use App\Http\Controllers\Hr\DepartmentController;
use App\Http\Controllers\Hr\DesignationController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\EmploymentHistoryController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::redirect('/', '/dashboard');

Route::get('/self-service', \App\Http\Controllers\SelfServiceController::class)
    ->middleware('auth')
    ->name('self-service.index');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});


Route::prefix('hr')->name('hr.')->middleware('auth')->group(function () {
Route::get('/organization-structure', function () {
            return view('hr.shell-empty', [
                'pageTitle' => 'Person',
                'section' => 'person'
            ]);
        })->name('organization-structure.index');

 Route::get('/employee-management', function () {
            return view('hr.shell-empty', [
                'pageTitle' => 'Employees',
                'section' => 'person'
            ]);
        })->name('employee-management.index');

Route::get('/shifts-attendance', function () {
            return view('hr.shell-empty', [
                'pageTitle' => 'Attendance',
                'section' => 'attendance'
            ]);
        })->name('shifts-attendance.index');

Route::get('/leaves', function () {
            return view('hr.shell-empty', [
                'pageTitle' => 'Leaves',
                'section' => 'attendance'
            ]);
        })->name('leaves.index');

Route::get('/device', [\App\Http\Controllers\Hr\BiometricDeviceController::class, 'index'])->name('device.index');

Route::get('/payroll', function () {
            return view('hr.shell-empty', [
                'pageTitle' => 'Payroll',
                'section' => 'payroll'
            ]);
        })->name('payroll.index');

        Route::get('/reports', function () {
            return view('hr.shell-empty', [
                'pageTitle' => 'Reports',
                'section' => 'payroll'
            ]);
        })->name('reports.index');
    });


// Add these inside your existing auth middleware group.

Route::prefix('hr/person')
    ->name('hr.person.')
    ->group(function () {
Route::get('/resigned', [\App\Http\Controllers\Hr\EmployeeResignationController::class, 'index'])->middleware('auth')->name('resigned');
Route::get('/to-be-reviewed', [\App\Http\Controllers\Hr\BiometricStagingController::class, 'index'])->middleware('auth')->name('to-be-reviewed');
Route::get('/position-management', [\App\Http\Controllers\Hr\PositionManagementController::class, 'index'])->middleware('auth')->name('position-management');

    });
Route::get('/hr/person', [
    \App\Http\Controllers\Hr\PersonPageController::class,
    'index',
])->name('hr.person.index');

Route::get('/person', [
    \App\Http\Controllers\Hr\PersonPageController::class,
    'index'
])->name('person.index');

Route::post('/person/departments', [
    \App\Http\Controllers\Hr\PersonPageController::class,
    'storeDepartment'
])->name('hr.person.departments.store');

Route::post('/person/employees', [
    \App\Http\Controllers\Hr\PersonPageController::class,
    'storeEmployee'
])->name('hr.person.employees.store');

Route::middleware(['auth'])->group(function () {
Route::get('/hr/person/employees/{employee}/photo', [\App\Http\Controllers\Hr\PersonPageController::class,'directoryPhoto',
    ])->name('hr.person.employees.photo');
Route::get('/hr/person/add-employee/lookup', [
        \App\Http\Controllers\Hr\PersonPageController::class,
        'lookupEmployee',
    ])->name('hr.person.lookup');
Route::post('/hr/person/employees/{employee}/bank-details', [
        \App\Http\Controllers\Hr\PersonPageController::class,
        'bankDetails',
    ])->middleware('throttle:6,1')->name('hr.person.bank-details');
Route::post('/hr/person/add-employee/section', [
        \App\Http\Controllers\Hr\PersonPageController::class,
        'saveSection',
    ])->middleware('throttle:20,1')->name('hr.person.sections.store');
Route::get('/hr/person/add-employee/{employee}/documents/{document}', [
        \App\Http\Controllers\Hr\PersonPageController::class, 'downloadDocument',
    ])->name('hr.person.documents.download');
Route::get('/hr/person/add-employee/{employee}/photo', [
        \App\Http\Controllers\Hr\PersonPageController::class,
        'registrationPhoto',
    ])->name('hr.person.registration.photo');
Route::get('/hr/person/add-employee', [
        \App\Http\Controllers\Hr\PersonPageController::class,
        'create',
    ])->name('hr.person.create');
});



require __DIR__.'/auth.php';

Route::middleware('auth')->prefix('hr/person/employees/{employeeId}/history')->name('hr.person.history.')->group(function () {
    Route::get('/', [EmploymentHistoryController::class, 'index'])->name('index');
    Route::post('/', [EmploymentHistoryController::class, 'store'])->name('store');
    Route::post('/transfer', [EmploymentHistoryController::class, 'transfer'])->name('transfer');
});

Route::middleware('auth')->prefix('hr/person/additional-information')->name('hr.person.additional-information')->controller(\App\Http\Controllers\Hr\AdditionalInformationController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store')->name('.store');
    Route::put('/{field}', 'update')->name('.update');
    Route::delete('/{field}', 'destroy')->name('.destroy');
});

Route::middleware('auth')->prefix('hr/person')->name('hr.person.')->group(function () {
    Route::get('/document-types', [\App\Http\Controllers\Hr\DocumentTypeController::class, 'index'])->name('document-types.index');
    Route::post('/document-types', [\App\Http\Controllers\Hr\DocumentTypeController::class, 'store'])->name('document-types.store');
    Route::put('/document-types/{type}', [\App\Http\Controllers\Hr\DocumentTypeController::class, 'update'])->name('document-types.update');
    Route::get('/system-alarms', [\App\Http\Controllers\Hr\SystemAlarmController::class, 'index'])->name('system-alarms.index');
    Route::post('/add-employee/{employee}/complete-onboarding', [\App\Http\Controllers\Hr\PersonPageController::class, 'completeOnboarding'])->name('onboarding.complete');
});

Route::post('/hr/person/employees/{employee}/resign', [\App\Http\Controllers\Hr\EmployeeResignationController::class, 'store'])
    ->middleware('auth')->name('hr.person.employees.resign');

Route::middleware('auth')->prefix('hr/person/position-management')->name('hr.person.positions.')->group(function () {
    Route::post('/', [\App\Http\Controllers\Hr\PositionManagementController::class, 'store'])->name('store');
    Route::put('/{designation}', [\App\Http\Controllers\Hr\PositionManagementController::class, 'update'])->name('update');
    Route::delete('/{designation}', [\App\Http\Controllers\Hr\PositionManagementController::class, 'destroy'])->name('destroy');
});

Route::middleware('auth')->prefix('hr/person/notifications')->name('hr.person.notifications.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Hr\AlarmNotificationController::class, 'index'])->name('index');
    Route::post('/enrollments/{staging}/open', [\App\Http\Controllers\Hr\AlarmNotificationController::class, 'openEnrollment'])->name('enrollment.open');
    Route::post('/{alarm}/open', [\App\Http\Controllers\Hr\AlarmNotificationController::class, 'open'])->name('open');
});

Route::middleware('auth')->prefix('hr/device')->name('hr.device.')->group(function () {
    Route::post('/', [\App\Http\Controllers\Hr\BiometricDeviceController::class, 'store'])->name('store');
    Route::get('/{device}/edit', [\App\Http\Controllers\Hr\BiometricDeviceController::class, 'edit'])->name('edit');
    Route::put('/{device}', [\App\Http\Controllers\Hr\BiometricDeviceController::class, 'update'])->name('update');
});

Route::middleware('auth')->prefix('hr/person/enrollments')->name('hr.person.enrollments.')->group(function () {
    Route::post('/', [\App\Http\Controllers\Hr\BiometricStagingController::class, 'store'])->name('store');
    Route::post('/{staging}/ignore', [\App\Http\Controllers\Hr\BiometricStagingController::class, 'ignore'])->name('ignore');
    Route::post('/{staging}/promote', [\App\Http\Controllers\Hr\BiometricStagingController::class, 'promote'])->name('promote');
});
