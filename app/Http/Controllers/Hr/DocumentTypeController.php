<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\DocumentTypeMaster;
use App\Services\EmployeeDocumentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentTypeController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->canManageEmployeeDocuments(), 403);

        return view('hr.person.document-types', ['types' => DocumentTypeMaster::orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->canManageEmployeeDocuments(), 403);
        $destination = $this->destination($request);
        DocumentTypeMaster::create($this->validated($request));
        app(EmployeeDocumentService::class)->refreshAlarms();

        return redirect($destination)->with('status', 'Document type created.');
    }

    public function update(Request $request, DocumentTypeMaster $type)
    {
        abort_unless($request->user()->canManageEmployeeDocuments(), 403);
        $destination = $this->destination($request);
        $type->update($this->validated($request, $type));
        app(EmployeeDocumentService::class)->refreshAlarms();

        return redirect($destination)->with('status', 'Document type updated.');
    }

    private function destination(Request $request): string
    {
        $context = $request->validate([
            'document_type_context' => ['nullable', Rule::in(['registration'])],
            'document_employee_id' => ['nullable', 'integer'],
        ]);
        if (($context['document_type_context'] ?? null) !== 'registration') {
            return route('hr.person.document-types.index');
        }
        $parameters = ['tab' => 'documents'];
        if (! empty($context['document_employee_id'])) {
            $id = (int) $context['document_employee_id'];
            abort_unless(in_array($id, $request->session()->get('person_registration_ids', []), true), 403);
            $parameters['employee'] = $id;
        }

        return route('hr.person.create', $parameters);
    }

    private function validated(Request $request, ?DocumentTypeMaster $type = null): array
    {
        $data = $request->validate([
            'code' => $type ? ['prohibited'] : ['required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:document_type_masters,code'],
            'name' => ['required', 'string', 'max:150', Rule::unique('document_type_masters')->ignore($type?->id)],
            'is_mandatory' => ['required', 'boolean'],
            'requires_expiry' => ['required', 'boolean'],
            'reminder_days' => ['required', 'integer', 'between:0,365'],
        ]);

        return $data;
    }
}
