<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\BiometricDevice;
use App\Models\Branch;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BiometricDeviceController extends Controller
{
    public function index(Request $request)
    {
        return $this->page($request);
    }

    public function edit(Request $request, BiometricDevice $device)
    {
        return $this->page($request, $device);
    }

    private function page(Request $request, ?BiometricDevice $device = null)
    {
        abort_unless($request->user()->canManageBiometricDevices(), 403);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'active' => ['nullable', Rule::in(['1', '0'])],
        ]);
        $query = BiometricDevice::with(['branch', 'department'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")->orWhere('serial_number', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%")->orWhere('ip_address', 'like', "%{$search}%")))
            ->when($filters['branch_id'] ?? null, fn ($query, $branch) => $query->where('branch_id', $branch));
        if (isset($filters['active']) && $filters['active'] !== '') {
            $query->where('is_active', $filters['active'] === '1');
        }

        return view('hr.device.index', [
            'devices' => $query->orderBy('name')->paginate(12)->withQueryString(), 'editingDevice' => $device,
            'branches' => Branch::orderBy('name')->get(), 'departments' => Department::orderBy('name')->get(),
            'timezones' => \DateTimeZone::listIdentifiers(), 'filters' => $filters,
            'totalDevices' => BiometricDevice::count(), 'activeDevices' => BiometricDevice::where('is_active', true)->count(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->canManageBiometricDevices(), 403);
        BiometricDevice::create($this->validated($request));

        return to_route('hr.device.index')->with('status', 'Device registered. It will show its sync status after the biometric bridge is connected.');
    }

    public function update(Request $request, BiometricDevice $device)
    {
        abort_unless($request->user()->canManageBiometricDevices(), 403);
        $device->update($this->validated($request, $device));

        return to_route('hr.device.index')->with('status', 'Device settings updated.');
    }

    private function validated(Request $request, ?BiometricDevice $device = null): array
    {
        foreach (['name', 'brand', 'serial_number', 'ip_address'] as $field) {
            if (is_string($request->input($field))) {
                $request->merge([$field => trim($request->input($field))]);
            }
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('branch_id', $request->input('branch_id'))],
            'brand' => ['required', 'string', 'max:100'],
            'serial_number' => ['required', 'string', 'max:100', Rule::unique('biometric_devices')->ignore($device?->id)],
            'ip_address' => ['required', 'ip'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'timezone' => ['required', 'timezone', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
