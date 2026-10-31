<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\BiometricDevice;
use App\Models\BiometricStagingUser;
use App\Models\Employee;
use App\Services\BiometricOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class BiometricStagingController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->canReviewBiometricEnrollments(), 403);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([BiometricStagingUser::PENDING, BiometricStagingUser::PROMOTED, BiometricStagingUser::IGNORED, 'All'])],
        ]);
        $status = $filters['status'] ?? BiometricStagingUser::PENDING;
        $enrollments = BiometricStagingUser::with(['device', 'branch', 'promotedEmployee'])
            ->when($status !== 'All', fn ($query) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('raw_enrollment_id', 'like', "%{$search}%")->orWhere('raw_name', 'like', "%{$search}%")))
            ->orderByDesc('id')->paginate(15)->withQueryString();
        $existingIds = Employee::withTrashed()->whereIn('employee_id', $enrollments->pluck('raw_enrollment_id'))->pluck('employee_id')->all();

        return response()->view('hr.person.to-be-reviewed', [
            'enrollments' => $enrollments, 'existingIds' => $existingIds, 'filters' => $filters, 'selectedStatus' => $status,
            'devices' => BiometricDevice::with('branch')->where('is_active', true)->orderBy('name')->get(),
            'pendingCount' => BiometricStagingUser::where('status', BiometricStagingUser::PENDING)->count(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, BiometricOnboardingService $onboarding)
    {
        abort_unless($request->user()->canReviewBiometricEnrollments(), 403);
        $data = $request->validate([
            'device_id' => ['required', 'integer', 'exists:biometric_devices,id'],
            'raw_enrollment_id' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+$/'],
            'raw_name' => ['nullable', 'string', 'max:255'],
        ]);
        $row = $onboarding->stage(BiometricDevice::findOrFail($data['device_id']), $data['raw_enrollment_id'], $data['raw_name'] ?? null);

        return to_route('hr.person.to-be-reviewed')->with('status', $row->wasRecentlyCreated ? 'Enrollment added for HR review.' : 'This device enrollment is already recorded. Its review status was retained.');
    }

    public function ignore(Request $request, BiometricStagingUser $staging, BiometricOnboardingService $onboarding)
    {
        abort_unless($request->user()->canReviewBiometricEnrollments(), 403);
        $data = $request->validate(['ignore_reason' => ['required', 'string', 'max:1000']]);
        $onboarding->ignore($staging, $request->user(), $data['ignore_reason']);

        return to_route('hr.person.to-be-reviewed')->with('status', 'Enrollment marked as Ignored.');
    }

    public function promote(Request $request, BiometricStagingUser $staging, BiometricOnboardingService $onboarding)
    {
        abort_unless($request->user()->canReviewBiometricEnrollments(), 403);
        $data = $request->validate([
            'record_id' => ['prohibited'],
            'employee_id' => ['required', 'string', Rule::in([$staging->raw_enrollment_id])],
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => ['required', 'integer', Rule::in([$staging->branch_id])],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('branch_id', $staging->branch_id)],
            'designation_id' => ['required', 'integer', Rule::exists('designations', 'id')->where('is_active', true)],
            'manager_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->whereNull('deleted_at')->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'Resigned'))],
            'joined_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Colombo')->toDateString()],
            'employment_type' => ['required', Rule::in(['Probation', 'Permanent', 'Contract', 'Intern'])],
            'probation_end_date' => ['exclude_unless:employment_type,Probation', 'nullable', 'date_format:Y-m-d', 'after_or_equal:joined_date'],
            'confirmed_date' => ['exclude_unless:employment_type,Permanent', 'nullable', 'date_format:Y-m-d', 'after_or_equal:joined_date', 'before_or_equal:'.now('Asia/Colombo')->toDateString()],
            'nic_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255', 'unique:employees,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'create_login' => ['required', 'boolean'],
            'login_email' => ['exclude_unless:create_login,1', 'required', 'email', 'max:255', 'unique:users,email'],
            'login_password' => ['exclude_unless:create_login,1', 'required', 'string', Password::min(8), 'confirmed'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $photoPath = null;
        try {
            if ($request->hasFile('profile_photo')) {
                $photo = $request->file('profile_photo');
                $photoPath = $photo->storeAs('', Str::uuid().'.'.$photo->extension(), 'employee_photos');
            }
            $data['profile_photo'] = $photoPath;
            $employee = $onboarding->promote($staging, $data, $request->user());
        } catch (\Throwable $error) {
            if ($photoPath) {
                Storage::disk('employee_photos')->delete($photoPath);
            }
            throw $error;
        }
        $ids = $request->session()->get('person_registration_ids', []);
        $request->session()->put('person_registration_ids', array_values(array_unique([...$ids, $employee->id])));
        $destination = route('hr.person.create', ['employee' => $employee->id, 'tab' => 'private']);
        if ($request->expectsJson()) {
            return response()->json(['id' => $employee->id, 'redirect_url' => $destination, 'message' => 'Enrollment promoted. Complete the employee profile and documents.'])
                ->header('Cache-Control', 'private, no-store');
        }

        return redirect($destination)->with('status', 'Enrollment promoted to employee.');
    }
}
