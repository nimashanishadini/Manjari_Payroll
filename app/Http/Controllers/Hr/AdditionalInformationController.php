<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAdditionalField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdditionalInformationController extends Controller
{
    public function index(): View
    {
        return view('hr.person.additional-information', [
            'fields' => EmployeeAdditionalField::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        EmployeeAdditionalField::create($this->validated($request));

        return to_route('hr.person.additional-information')->with('status', 'Additional field created.');
    }

    public function update(Request $request, EmployeeAdditionalField $field): RedirectResponse
    {
        $field->update($this->validated($request, $field));

        return to_route('hr.person.additional-information')->with('status', 'Additional field updated.');
    }

    public function destroy(EmployeeAdditionalField $field): RedirectResponse
    {
        $field->delete();

        return to_route('hr.person.additional-information')->with('status', 'Additional field deleted.');
    }

    private function validated(Request $request, ?EmployeeAdditionalField $field = null): array
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('employee_additional_fields')->ignore($field?->id)],
            'type' => ['required', Rule::in(['text', 'number', 'date', 'select'])],
            'visibility' => ['required', Rule::in(['private', 'public'])],
            'show_in_person_list' => ['required', 'boolean'],
            'options_text' => [Rule::requiredIf($request->input('type') === 'select'), 'nullable', 'string', 'max:4000'],
        ]);
        $options = array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/', $data['options_text'] ?? '')), fn ($value) => $value !== '')));
        if ($data['type'] === 'select' && (count($options) < 2 || count($options) > 50)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'options_text' => 'Enter between 2 and 50 different choices, one per line.',
            ]);
        }
        unset($data['options_text']);
        $data['options'] = $data['type'] === 'select' ? $options : null;

        return $data;
    }
}
