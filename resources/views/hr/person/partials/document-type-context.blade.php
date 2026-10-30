@if($registrationContext)
    <input type="hidden" name="document_type_context" value="registration">
    <input type="hidden" name="document_employee_id" data-document-type-employee value="{{ $employee?->id }}">
@endif
