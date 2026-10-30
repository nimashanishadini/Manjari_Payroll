<div class="dt-settings">
    @if(session('status'))<p role="status" class="dt-success">{{ session('status') }}</p>@endif
    @if($errors->any())<div role="alert" class="dt-errors">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <details class="dt-create" @if(old('document_type_editor') === 'create') open @endif>
        <summary><span class="dt-plus" aria-hidden="true">＋</span><div><strong>Add document type</strong><small>Create a new document category for employee profiles.</small></div><span class="dt-chevron" aria-hidden="true">⌄</span></summary>
        <form method="POST" action="{{ route('hr.person.document-types.store') }}" class="dt-form">
            @csrf
            @include('hr.person.partials.document-type-context', ['registrationContext' => $registrationContext ?? false])
            @include('hr.person.partials.document-type-fields', ['editingType' => null])
            <div class="dt-form-footer"><button class="erp-primary-action dt-save">Create document type</button></div>
        </form>
    </details>
    <div class="dt-list-heading"><h3>Document types</h3><span>{{ $types->count() }} types</span></div>
    <p class="dt-list-help">Select a document type to edit its settings. Changes apply to all employees.</p>
    <div class="dt-type-list">
        @foreach($types as $type)
            <details class="dt-type" @if(old('document_type_editor') === (string) $type->id) open @endif>
                <summary>
                    <div class="dt-type-name"><strong>{{ $type->name }}</strong><small>{{ $type->code }}</small></div>
                    <div class="dt-type-badges"><span class="{{ $type->is_mandatory ? 'dt-required' : '' }}">{{ $type->is_mandatory ? 'Mandatory' : 'Optional' }}</span><span>{{ $type->requires_expiry ? 'Expiry required' : 'No expiry' }}</span></div>
                    <span class="dt-chevron" aria-hidden="true">⌄</span>
                </summary>
                <form method="POST" action="{{ route('hr.person.document-types.update', $type) }}" class="dt-form">
                    @csrf @method('PUT')
                    @include('hr.person.partials.document-type-context', ['registrationContext' => $registrationContext ?? false])
                    @include('hr.person.partials.document-type-fields', ['editingType' => $type])
                    <div class="dt-form-footer"><button class="erp-primary-action dt-save">Save settings</button></div>
                </form>
            </details>
        @endforeach
    </div>
</div>
<style>
.dt-settings [data-expiry-reminder][hidden]{display:none!important}
.dt-settings{color:#334155;font-size:14px}.dt-create{margin-top:22px;border:1px solid #e2e8f0;border-radius:12px;background:#fff}.dt-create>summary,.dt-type>summary{display:flex;align-items:center;gap:12px;padding:16px 18px;cursor:pointer;list-style:none}.dt-settings summary::-webkit-details-marker{display:none}.dt-plus{display:grid;place-items:center;width:34px;height:34px;border-radius:9px;background:#fff1f2;color:#be123c;font-size:21px;flex:none}.dt-settings summary strong{display:block;font-size:14px;font-weight:650;color:#0f172a}.dt-settings summary small{display:block;margin-top:4px;font-size:12px;color:#64748b;overflow-wrap:anywhere}.dt-chevron{margin-left:auto;color:#64748b;font-size:20px;transition:transform .2s}.dt-settings details[open]>summary .dt-chevron{transform:rotate(180deg)}.dt-form{padding:20px;border-top:1px solid #e2e8f0;background:#fafbfc;border-radius:0 0 12px 12px}.dt-field-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px 24px;align-items:start}.dt-field{min-width:0}.dt-full{grid-column:1/-1}.dt-field>label{display:block;margin-bottom:8px;font-size:13px;font-weight:600;color:#334155}.dt-field>label>span{color:#be123c}.dt-settings input:not([type=hidden]),.dt-settings select{display:block;box-sizing:border-box;width:100%;min-height:44px;border:1px solid #cbd5e1;border-radius:8px;padding:10px 12px;background:#fff;color:#0f172a;font:inherit;font-size:14px;line-height:1.5}.dt-settings select{padding-right:32px}.dt-settings input::placeholder{color:#94a3b8}.dt-settings input:read-only{background:#f1f5f9;color:#64748b}.dt-settings input:focus,.dt-settings select:focus{outline:2px solid #cbd5e1;outline-offset:1px;border-color:#64748b;box-shadow:none}.dt-field>p{margin-top:7px;font-size:12px;line-height:1.6;color:#64748b}.dt-days{display:flex;align-items:center;gap:10px;max-width:220px}.dt-days>span{font-size:13px;color:#64748b}.dt-form-footer{display:flex;justify-content:flex-end;margin-top:22px;padding-top:16px;border-top:1px solid #e2e8f0}.dt-save{min-height:42px;padding:10px 18px;border-radius:8px;font-size:13px;font-weight:650}.dt-list-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:28px}.dt-list-heading h3{font-size:15px;font-weight:700;color:#0f172a}.dt-list-heading>span{padding:4px 9px;border-radius:6px;background:#f1f5f9;font-size:12px;color:#64748b}.dt-list-help{margin:7px 0 14px;font-size:12px;line-height:1.6;color:#64748b}.dt-type-list{display:grid;gap:10px}.dt-type{border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#fff}.dt-type>summary:hover{background:#f8fafc}.dt-type-name{flex:1;min-width:0}.dt-type-badges{display:flex;flex-wrap:wrap;gap:6px}.dt-type-badges>span{font-size:11px;white-space:nowrap;color:#64748b;background:#f1f5f9;border-radius:5px;padding:4px 8px}.dt-type-badges>.dt-required{background:#fff1f2;color:#9f1239}.dt-success,.dt-errors{margin-top:16px;padding:12px 14px;border-radius:8px;font-size:13px;line-height:1.7}.dt-success{background:#f0fdf4;color:#166534}.dt-errors{background:#fef2f2;color:#b91c1c}
@media(max-width:600px){.dt-field-grid{grid-template-columns:1fr;gap:18px}.dt-create>summary,.dt-type>summary{padding:14px;flex-wrap:wrap}.dt-type-name{flex-basis:calc(100% - 35px)}.dt-type-badges{order:3;width:100%}.dt-form{padding:16px}.dt-create small{max-width:230px}.dt-form-footer .dt-save{width:100%}}
</style>
