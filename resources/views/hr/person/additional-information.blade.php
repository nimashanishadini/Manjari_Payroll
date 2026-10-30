<x-layouts.app title="Additional Information" section="person">
    <section class="additional-page">
        <x-page-header overline="Person / Custom Fields" title="Additional Information" description="A little more detail. A more complete employee profile.">
            <x-slot:icon>
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M4 17h16" /><circle cx="9" cy="7" r="3" /><circle cx="15" cy="17" r="3" /></svg>
            </x-slot:icon>
            <x-slot:actions>
                <button type="button" class="additional-primary" id="createField">＋ Create field</button>
            </x-slot:actions>
        </x-page-header>

        @if(session('status'))
            <p class="additional-success" role="status">{{ session('status') }}</p>
        @endif

        <div class="additional-overview">
            <div class="additional-intro"><span class="additional-symbol" aria-hidden="true">Aa</span><div><h2>Make room for what matters</h2><p>Define extra fields for employee information beyond the standard profile.</p></div></div>
            <div class="additional-metric"><strong>{{ $fields->count() }}</strong><span>Total fields</span></div>
            <div class="additional-metric"><strong>{{ $fields->where('visibility', 'private')->count() }}</strong><span>Private fields</span></div>
            <div class="additional-metric"><strong>{{ $fields->where('show_in_person_list', true)->count() }}</strong><span>List columns</span></div>
        </div>

        <div class="additional-toolbar">
            <div><h2>Your custom fields</h2><p>Organize the details your team needs.</p></div>
            <div class="additional-filters"><input type="search" id="fieldSearch" aria-label="Search fields" placeholder="Search field names…"><select id="visibilityFilter" aria-label="Filter by visibility"><option value="">All visibility</option><option value="private">Private</option><option value="public">Public</option></select></div>
        </div>

        <div class="additional-table-scroll" id="fieldGrid" @if($fields->isEmpty()) hidden @endif>
            <table class="additional-fields-table">
                <thead><tr><th>Field name</th><th>Information type</th><th>Visibility</th><th>Display in person list</th><th>Actions</th></tr></thead>
                <tbody>
                    @foreach($fields as $field)
                        <tr class="additional-card" data-name="{{ strtolower($field->name) }}" data-visibility="{{ $field->visibility }}">
                            <td><div class="additional-row-name"><span class="additional-type-icon" aria-hidden="true">{{ ['text' => 'Aa', 'number' => '#', 'date' => '◷', 'select' => '☷'][$field->type] }}</span><strong>{{ $field->name }}</strong></div></td>
                            <td>{{ ['text' => 'General text', 'number' => 'Number', 'date' => 'Date', 'select' => 'Single selection'][$field->type] }}@if($field->type === 'select')<small>{{ count($field->options ?? []) }} choices</small>@endif</td>
                            <td><span class="additional-badge {{ $field->visibility === 'public' ? 'is-public' : '' }}">{{ ucfirst($field->visibility) }}</span></td>
                            <td>{{ $field->show_in_person_list ? 'Yes' : 'No' }}</td>
                            <td><div class="additional-row-actions">
                                <button type="button" data-edit-field="{{ $field->id }}" aria-label="Edit {{ $field->name }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 3 5 5L9 20l-6 1 1-6L16 3ZM14 5l5 5"/></svg>Edit field</button>
                                <button type="button" class="additional-delete" data-delete-field="{{ $field->id }}" aria-label="Delete {{ $field->name }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/></svg>Delete</button>
                            </div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="additional-empty" id="emptyFields" @if($fields->isNotEmpty()) hidden @endif><span aria-hidden="true">＋</span><h3 id="emptyTitle">Start with your first custom field</h3><p id="emptyDescription">Create a text, number, date or selection field to organize additional information.</p></div>
        <p class="additional-note">Tip: use short, clear field names so your team can recognize them easily.</p>
    </section>

    <dialog id="fieldDialog" class="additional-dialog" aria-labelledby="editorTitle">
        <form id="fieldForm" method="POST" action="{{ route('hr.person.additional-information.store') }}">
            @csrf
            <input type="hidden" name="_method" id="fieldMethod" value="POST">
            <input type="hidden" name="editing_id" id="editingId" value="">
            <div class="additional-dialog-heading"><div><span class="additional-eyebrow">CUSTOMIZE YOUR DIRECTORY</span><h2 id="editorTitle">Create a field</h2></div><button type="button" class="additional-close" data-close-dialog aria-label="Close field editor">×</button></div>
            <p class="additional-dialog-intro">Choose the information you want to collect.</p>
            @if($errors->any())<div class="additional-errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <label for="fieldName">Field name <span>*</span></label><input id="fieldName" name="name" required maxlength="100" placeholder="e.g. Uniform size">
            <label for="fieldType">Information type <span>*</span></label><select id="fieldType" name="type" required><option value="text">General text</option><option value="number">Number</option><option value="date">Date</option><option value="select">Single selection</option></select>
            <div id="fieldOptions" hidden><label for="optionsText">Choices <span>*</span></label><textarea id="optionsText" name="options_text" rows="4" maxlength="4000" placeholder="Small&#10;Medium&#10;Large"></textarea><small>One choice per line. Add between 2 and 50 choices.</small></div>
            <fieldset><legend>Sharing property</legend><div class="additional-radio-grid"><label><input type="radio" name="visibility" value="private" checked><span><strong>Private</strong><small>For restricted HR information</small></span></label><label><input type="radio" name="visibility" value="public"><span><strong>Public</strong><small>For general employee information</small></span></label></div></fieldset>
            <div class="additional-checkbox"><input type="hidden" name="show_in_person_list" value="0"><label><input type="checkbox" name="show_in_person_list" id="showInList" value="1"><span><strong>Display in person list</strong><small>Mark this field for a directory column.</small></span></label></div>
            <div class="additional-dialog-footer"><button type="button" class="additional-secondary" data-close-dialog>Cancel</button><button type="submit" class="additional-primary" id="saveField">Create field</button></div>
        </form>
    </dialog>
    <dialog id="deleteDialog" class="additional-dialog additional-confirm" aria-labelledby="deleteTitle"><form method="POST" id="deleteForm">@csrf @method('DELETE')<h2 id="deleteTitle">Delete this field?</h2><p id="deleteDescription"></p><div class="additional-dialog-footer"><button type="button" class="additional-secondary" id="cancelDelete">Cancel</button><button type="submit" class="additional-primary">Delete field</button></div></form></dialog>

    <style>
        .additional-page{padding:36px;min-height:calc(100vh - 60px);background:#f7f8fa;color:#182230}.additional-heading,.additional-toolbar,.additional-filters,.additional-card-top,.additional-card-detail,.additional-card-actions,.additional-dialog-heading,.additional-dialog-footer{display:flex;align-items:center;justify-content:space-between;gap:16px}.additional-eyebrow{font-size:10px;letter-spacing:1.8px;font-weight:700;color:#8c5962}.additional-heading h1{font-size:28px;font-weight:750;letter-spacing:-.8px;margin:8px 0}.additional-heading p,.additional-toolbar p{font-size:13px;color:#76808e;margin:4px 0}.additional-primary,.additional-secondary{border:0;border-radius:9px;padding:12px 18px;font-size:13px;font-weight:650;cursor:pointer;white-space:nowrap}.additional-primary{background:#e91d42;color:white;box-shadow:0 4px 10px #e91d4218}.additional-primary:hover{background:#c51435}.additional-secondary{background:#eef0f4;color:#485366}.additional-overview{display:grid;grid-template-columns:1fr repeat(3,110px);gap:24px;align-items:center;background:#fff;border:1px solid #e6e9ef;border-radius:16px;padding:28px;margin:30px 0 36px}.additional-intro{display:flex;gap:16px;align-items:center}.additional-symbol{display:grid;place-items:center;width:50px;height:50px;flex-shrink:0;background:#fff0f3;color:#ce2444;border-radius:13px;font-size:22px}.additional-intro h2,.additional-toolbar h2{font-size:16px;font-weight:700;margin:0}.additional-intro p{font-size:12px;line-height:1.7;color:#76808e;max-width:300px;margin:5px 0 0}.additional-metric{border-left:1px solid #edf0f4;padding-left:22px}.additional-metric strong{display:block;font-size:27px;font-weight:700}.additional-metric span{font-size:11px;color:#7b8594}.additional-toolbar{margin-bottom:22px}.additional-filters input,.additional-filters select{padding:11px 13px;border:1px solid #e0e4eb;border-radius:9px;background:white;font-size:12px;color:#4c5666}.additional-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.additional-card{background:white;border:1px solid #e3e7ed;border-radius:14px;padding:22px;box-shadow:0 3px 10px #18223003}.additional-type-icon{display:grid;place-items:center;width:39px;height:39px;background:#f2f4f7;border-radius:10px;color:#526279;font-size:20px;font-weight:600}.additional-badge{font-size:10px;font-weight:650;padding:5px 9px;border-radius:20px;color:#785a34;background:#faf3e8}.additional-badge.is-public{background:#eaf6f1;color:#277358}.additional-card h3{font-size:16px;font-weight:700;margin:19px 0 5px;overflow-wrap:anywhere}.additional-type{font-size:12px;color:#88909d;margin:0 0 23px}.additional-card-detail{font-size:11px;border-top:1px solid #f0f1f4;padding:15px 0;color:#7c8593}.additional-card-detail strong{color:#526073;font-weight:600}.additional-card-actions{border-top:1px solid #f0f1f4;padding-top:15px}.additional-card-actions button{background:none;border:0;padding:0;cursor:pointer;font-size:12px;font-weight:600;color:#445268}.additional-card-actions .additional-delete{color:#b77882;font-size:11px}.additional-empty{text-align:center;border:1px dashed #d9dfe8;background:#fff;border-radius:14px;padding:60px 20px;color:#798394}.additional-empty>span{font-size:28px;color:#dc3553}.additional-empty h3{font-weight:650;color:#344054;margin:10px 0;font-size:17px}.additional-empty p,.additional-note{font-size:12px;line-height:1.7}.additional-note{color:#86909e;margin-top:24px}.additional-dialog{position:fixed;inset:0;margin:auto;border:0;border-radius:18px;padding:30px;width:min(520px,calc(100% - 32px));max-height:90vh;overflow:auto;box-shadow:0 24px 80px #10182833;color:#263244}.additional-dialog::backdrop{background:#10182880;backdrop-filter:blur(3px)}.additional-dialog h2{font-size:23px;font-weight:700;margin:6px 0}.additional-close{font-size:26px;background:#f5f6f8;border:0;border-radius:50%;width:34px;height:34px;cursor:pointer;color:#7d8591}.additional-dialog-intro{font-size:13px;color:#84909d;margin:7px 0 24px}.additional-dialog label,.additional-dialog legend{display:block;font-size:12px;font-weight:600;margin:18px 0 8px}.additional-dialog label>span:not(:only-child){color:inherit}.additional-dialog input:not([type=radio]):not([type=checkbox]):not([type=hidden]),.additional-dialog select,.additional-dialog textarea{width:100%;border:1px solid #dfe3ea;border-radius:8px;padding:11px 12px;font-size:13px;background:#fff;color:#344054}.additional-dialog small{display:block;font-size:11px;color:#8a93a1;font-weight:400;margin-top:4px}.additional-dialog fieldset{border:0;padding:0;margin:8px 0 0}.additional-radio-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.additional-radio-grid label{display:flex;align-items:flex-start;gap:9px;padding:14px 10px;border:1px solid #e1e5ed;border-radius:10px;margin:0;cursor:pointer}.additional-dialog input[type=radio],.additional-dialog input[type=checkbox]{accent-color:#e91d42;margin-top:3px}.additional-checkbox{background:#f7f8fa;border-radius:10px;padding:0 14px;margin-top:20px}.additional-checkbox label{display:flex;gap:10px;padding:15px 0;margin:0;cursor:pointer}.additional-dialog-footer{justify-content:flex-end;margin-top:28px;border-top:1px solid #edf0f4;padding-top:20px}.additional-errors{font-size:12px;background:#fff0f2;color:#b91c3c;padding:12px;border-radius:8px}.additional-success{background:#eaf6ef;color:#287349;padding:14px;border-radius:9px;font-size:13px;margin-top:20px}.additional-confirm p{color:#778292;font-size:13px;overflow-wrap:anywhere}.additional-page [hidden],.additional-dialog [hidden]{display:none!important}.additional-page :focus-visible,.additional-dialog :focus-visible{outline:2px solid #e91d42;outline-offset:3px}
        @media(min-width:1500px){.additional-grid{grid-template-columns:repeat(4,minmax(0,1fr))}}@media(max-width:1150px){.additional-overview{grid-template-columns:repeat(3,1fr)}.additional-intro{grid-column:1/-1}.additional-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.additional-toolbar{align-items:flex-start;flex-direction:column}.additional-metric:first-of-type{border:0}}@media(max-width:650px){.additional-page{padding:22px 16px}.additional-heading{align-items:flex-start;flex-direction:column}.additional-heading h1{font-size:24px}.additional-grid{grid-template-columns:1fr}.additional-overview{padding:20px;gap:18px}.additional-metric{padding-left:12px}.additional-filters{flex-wrap:wrap;width:100%}.additional-filters input{width:100%}.additional-dialog{padding:22px}.additional-radio-grid{grid-template-columns:1fr}}

        .additional-table-scroll{width:100%;overflow-x:auto;border:1px solid #e2e8f0;border-radius:12px;background:#fff}
        .additional-fields-table{width:100%;min-width:760px;border-collapse:collapse;text-align:left;font-size:14px}
        .additional-fields-table th{padding:14px 18px;background:#f8fafc;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;white-space:nowrap}
        .additional-fields-table .additional-card{border:0;border-radius:0;padding:0;box-shadow:none;background:#fff}
        .additional-fields-table .additional-card:hover{background:#f8fafc;transform:none;box-shadow:none}
        .additional-fields-table td{padding:16px 18px;border-top:1px solid #edf0f4;color:#475569;vertical-align:middle}
        .additional-row-name{display:flex;align-items:center;gap:12px}.additional-row-name strong{font-weight:600;color:#0f172a;overflow-wrap:anywhere}
        .additional-fields-table td small{display:block;font-size:12px;color:#94a3b8;margin-top:4px}
        .additional-row-actions{display:flex;align-items:center;gap:10px;white-space:nowrap}
        .additional-row-actions button{display:inline-flex;align-items:center;gap:5px;padding:7px 10px;border:1px solid #e2e8f0;border-radius:7px;background:#fff;font-size:12px;font-weight:600;color:#475569;cursor:pointer}
        .additional-row-actions button:hover{background:#f1f5f9}.additional-row-actions .additional-delete{color:#be123c;border-color:#fecdd3}
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fields = {{ Illuminate\Support\Js::from($fields->keyBy('id')) }};
            const storeUrl = @json(route('hr.person.additional-information.store'));
            const editor = document.getElementById('fieldDialog');
            const form = document.getElementById('fieldForm');
            const type = document.getElementById('fieldType');
            const options = document.getElementById('optionsText');
            const toggleOptions = () => {
                document.getElementById('fieldOptions').hidden = type.value !== 'select';
                options.required = type.value === 'select';
                options.disabled = type.value !== 'select';
            };
            const openEditor = (field = null) => {
                form.reset();
                form.querySelector('.additional-errors')?.remove();
                document.getElementById('editingId').value = field?.id ?? '';
                document.getElementById('fieldMethod').value = field ? 'PUT' : 'POST';
                form.action = field ? `${storeUrl}/${field.id}` : storeUrl;
                document.getElementById('editorTitle').textContent = field ? 'Edit field' : 'Create a field';
                document.getElementById('saveField').textContent = field ? 'Save changes' : 'Create field';
                document.getElementById('fieldName').value = field?.name ?? '';
                type.value = field?.type ?? 'text';
                options.value = (field?.options ?? []).join('\n');
                form.querySelector(`input[name="visibility"][value="${field?.visibility === 'public' ? 'public' : 'private'}"]`).checked = true;
                document.getElementById('showInList').checked = !!field?.show_in_person_list;
                toggleOptions();
                editor.showModal();
            };
            document.getElementById('createField').addEventListener('click', () => openEditor());
            type.addEventListener('change', toggleOptions);
            document.querySelectorAll('[data-close-dialog]').forEach(button => button.addEventListener('click', () => editor.close()));
            document.querySelectorAll('[data-edit-field]').forEach(button => button.addEventListener('click', () => openEditor(fields[button.dataset.editField])));
            const deletion = document.getElementById('deleteDialog');
            document.querySelectorAll('[data-delete-field]').forEach(button => button.addEventListener('click', () => {
                const field = fields[button.dataset.deleteField];
                document.getElementById('deleteForm').action = `${storeUrl}/${field.id}`;
                document.getElementById('deleteDescription').textContent = `“${field.name}” will be permanently removed from your custom field settings.`;
                deletion.showModal();
            }));
            document.getElementById('cancelDelete').addEventListener('click', () => deletion.close());
            const search = document.getElementById('fieldSearch');
            const visibility = document.getElementById('visibilityFilter');
            const filter = () => {
                let visible = 0;
                document.querySelectorAll('.additional-card').forEach(card => {
                    card.hidden = !card.dataset.name.includes(search.value.trim().toLowerCase()) || (!!visibility.value && card.dataset.visibility !== visibility.value);
                    if (!card.hidden) visible++;
                });
                document.getElementById('emptyFields').hidden = visible > 0;
                document.getElementById('fieldGrid').hidden = visible === 0;
                if (Object.keys(fields).length) {
                    document.getElementById('emptyTitle').textContent = 'No matching fields';
                    document.getElementById('emptyDescription').textContent = 'Try another name or visibility filter.';
                }
            };
            search.addEventListener('input', filter);
            visibility.addEventListener('change', filter);
            @if($errors->any())
                const previous = {{ Illuminate\Support\Js::from(old()) }};
                const errorBox = form.querySelector('.additional-errors')?.cloneNode(true);
                openEditor(previous.editing_id ? fields[previous.editing_id] : null);
                if (errorBox) form.querySelector('.additional-dialog-intro').after(errorBox);
                document.getElementById('fieldName').value = previous.name ?? '';
                type.value = ['text', 'number', 'date', 'select'].includes(previous.type) ? previous.type : 'text';
                options.value = previous.options_text ?? '';
                form.querySelector(`input[name="visibility"][value="${previous.visibility === 'public' ? 'public' : 'private'}"]`).checked = true;
                document.getElementById('showInList').checked = String(previous.show_in_person_list) === '1';
                toggleOptions();
            @endif
        });
    </script>
</x-layouts.app>
