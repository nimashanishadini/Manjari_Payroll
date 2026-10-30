@php
    $editorKey = $editingType ? (string) $editingType->id : 'create';
    $restore = old('document_type_editor') === $editorKey;
    $typeName = $restore ? old('name') : ($editingType?->name ?? '');
    $mandatory = $restore ? old('is_mandatory', 0) : ($editingType?->is_mandatory ?? false);
    $expires = $restore ? old('requires_expiry', 0) : ($editingType?->requires_expiry ?? false);
    $days = $restore ? old('reminder_days', 30) : ($editingType?->reminder_days ?? 30);
@endphp
<input type="hidden" name="document_type_editor" value="{{ $editorKey }}">
<div class="dt-field-grid">
    <div class="dt-field {{ $editingType ? 'dt-full' : '' }}">
        <label for="dt-{{ $editorKey }}-name">Document name <span>*</span></label>
        <input id="dt-{{ $editorKey }}-name" name="name" value="{{ $typeName }}" required maxlength="150" placeholder="e.g. Work Permit">
    </div>
    @unless($editingType)
        <div class="dt-field">
            <label for="dt-create-code">Reference code <span>*</span></label>
            <input id="dt-create-code" name="code" value="{{ $restore ? old('code') : '' }}" required maxlength="80" pattern="[a-z][a-z0-9_]*" placeholder="e.g. work_permit" aria-describedby="dt-code-help">
            <p id="dt-code-help">Lowercase letters, numbers and underscores. This code cannot be changed later.</p>
        </div>
    @endunless
    <div class="dt-field">
        <label for="dt-{{ $editorKey }}-mandatory">Onboarding requirement</label>
        <select id="dt-{{ $editorKey }}-mandatory" name="is_mandatory"><option value="0">Optional</option><option value="1" @selected($mandatory)>Mandatory</option></select>
        <p>Mandatory documents are needed to complete onboarding.</p>
    </div>
    <div class="dt-field">
        <label for="dt-{{ $editorKey }}-expiry">Expiry date</label>
        <select id="dt-{{ $editorKey }}-expiry" name="requires_expiry" onchange="const expires = this.value === '1'; const reminder = this.form.querySelector('[name=reminder_days]'); this.form.querySelector('[data-expiry-reminder]').hidden = !expires; reminder.type = expires ? 'number' : 'hidden'; reminder.required = expires;"><option value="0">Does not expire</option><option value="1" @selected($expires)>Required</option></select>
        <p>Choose Required for documents such as driving licences.</p>
    </div>
    <div class="dt-field dt-full" data-expiry-reminder @if(!$expires) hidden @endif>
        <label for="dt-{{ $editorKey }}-reminder">Reminder before expiry</label>
        <div class="dt-days"><input id="dt-{{ $editorKey }}-reminder" type="{{ $expires ? 'number' : 'hidden' }}" name="reminder_days" value="{{ $days }}" min="0" max="365" @required($expires)><span>days</span></div>
        <p>Used only for documents with an expiry date. Choose 0–365 days.</p>
    </div>
</div>
