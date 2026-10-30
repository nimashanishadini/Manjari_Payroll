<div class="dq-workspace" id="documentWorkspace">
    <header class="dq-header">
        <div><h2>Documents & qualifications<span class="dq-accent">.</span></h2><p>Credentials, certificates and the details that support them.</p></div>
        @if(auth()->user()?->canManageEmployeeDocuments())
            <button type="button" onclick="openDocumentTypeSettings()" class="shrink-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Manage document types</button>
        @endif
    </header>
    @if(session('status'))
        <p role="status" class="my-4 rounded-lg bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</p>
    @endif
    <div class="dq-layout">
        <div class="dq-main">
            <div class="dq-section-heading"><span class="dq-step">01</span><div><h4>Add a document</h4><p>Choose a category and document type to get started.</p></div></div>
            <fieldset class="dq-categories"><legend>Document category</legend>
                <div class="dq-category-options">
                <label><input type="radio" name="document_category_picker" value="education" checked><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m2 9 10-5 10 5-10 5-10-5Zm4 2v6c4 3 8 3 12 0v-6M22 9v8"/></svg><strong>Educational</strong><small>Academic certificates</small></span></label>
                <label><input type="radio" name="document_category_picker" value="employment"><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="7" width="18" height="14" rx="3"/><path d="M8 7V4h8v3M3 12c6 4 12 4 18 0M12 12v4"/></svg><strong>Employment</strong><small>Identity & work records</small></span></label>
                <label><input type="radio" name="document_category_picker" value="other"><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9l-6-6Z"/><path d="M14 3v6h6M8 14h8M8 17h5"/></svg><strong>Professional & other</strong><small>Additional credentials</small></span></label>
                </div>
            </fieldset>
            <label for="documentTypePicker" class="dq-picker-label">Document type <span>*</span></label>
            <select id="documentTypePicker" class="dq-type-picker" aria-controls="documentEditors">
                @foreach($documentTypes as $type)
                    <option value="{{ $type->code }}" data-picker-group="{{ in_array($type->code, ['ol', 'al', 'higher_education']) ? 'education' : (array_key_exists($type->code, config('employee_documents')) ? 'employment' : 'other') }}">{{ $type->name }}{{ $type->is_mandatory ? ' · Mandatory' : '' }}</option>
                @endforeach
            </select>
            <p id="documentTypeEmpty" class="dq-empty" hidden>No document types in this category yet. An Admin or HR user can add them using Manage document types.</p>
            <div id="documentEditors">
@foreach(['Education & Qualifications' => $documentTypes->whereIn('code', ['ol', 'al', 'higher_education']), 'Personal & Employment Documents' => $documentTypes->whereNotIn('code', ['ol', 'al', 'higher_education'])] as $group => $types)
    <div class="dq-type-group">
        <div>
            @foreach($types as $type)
                @php($key = $type->code)
                <article class="dq-editor" hidden data-document-category="{{ $key }}" data-requires-expiry="{{ $type->requires_expiry ? '1' : '0' }}" data-document-group="{{ in_array($key, ['ol', 'al', 'higher_education']) ? 'education' : (array_key_exists($key, config('employee_documents')) ? 'employment' : 'other') }}">
                    <div class="dq-editor-heading">
                        <h4 class="dq-type-title">{{ $type->name }} @if($type->is_mandatory)<span class="dq-mandatory">Mandatory</span>@endif</h4>
                        <span data-document-count class="dq-count">No files saved</span>
                    </div>
                    @if(in_array($key, ['ol', 'al', 'higher_education']))
                        <div class="mt-4 grid gap-3 sm:grid-cols-[minmax(0,1fr)_100px]">
                            <div>
                                <label for="{{ $key }}-institution" class="text-sm font-medium text-slate-600">School / Institution</label>
                                <input id="{{ $key }}-institution" name="qualifications[{{ $key }}][institution]" data-qualification="institution" maxlength="255" placeholder="Institution name" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none">
                            </div>
                            <div>
                                <label for="{{ $key }}-year" class="text-sm font-medium text-slate-600">Year</label>
                                <input id="{{ $key }}-year" name="qualifications[{{ $key }}][year]" data-qualification="year" type="number" min="1900" max="{{ now()->year }}" placeholder="YYYY" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none">
                            </div>
                        </div>
                        <label for="{{ $key }}-details" class="mt-3 block text-sm font-medium text-slate-600">Qualification / Results</label>
                        <textarea id="{{ $key }}-details" name="qualifications[{{ $key }}][details]" data-qualification="details" rows="2" maxlength="5000" placeholder="Qualifications, subjects, grades or other details" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none"></textarea>
                    @endif
                    <div data-saved-documents class="dq-saved-files"></div>
                    <label for="{{ $key }}-files" class="dq-upload">
                        <span class="dq-upload-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M7 17H6a4 4 0 0 1-.5-8A6.5 6.5 0 0 1 18 7a5 5 0 0 1 0 10h-1M12 20V11m-4 4 4-4 4 4"/></svg></span>
                        <strong>Upload {{ in_array($key, ['ol', 'al', 'higher_education']) ? 'certificate' : 'document' }}</strong>
                        <span>Choose PDF, JPG, PNG or WebP files</span>
                        <span class="dq-upload-browse">Browse files <span aria-hidden="true">↗</span></span>
                        <input id="{{ $key }}-files" type="file" name="uploads[{{ $key }}][]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" aria-describedby="{{ $key }}-selection" class="dq-file-input">
                    </label>
                    @if($type->requires_expiry)
                        <label for="{{ $key }}-expiry" class="mt-3 block text-sm font-medium text-slate-600">Expiry date for new files (required)</label>
                        <input id="{{ $key }}-expiry" type="date" name="upload_expiry[{{ $key }}]" data-upload-expiry class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                    @else
                        <p class="mt-3 text-xs text-slate-500">No expiry date required.</p>
                    @endif
                    <p id="{{ $key }}-selection" data-file-selection role="status" class="mt-2 break-words text-xs text-slate-500"></p>
                </article>
            @endforeach
        </div>
    </div>
@endforeach
            </div>
            <div class="dq-save-hint"><span aria-hidden="true">↳</span> Use <strong>Save Documents & Qualifications</strong> below to save your changes.</div>
        </div>
        <aside class="dq-aside">
            <section class="dq-summary"><span class="dq-eyebrow">YOUR CHECKLIST</span><h4>Keep every record<br>in one place.</h4><div class="dq-summary-number"><strong id="documentSavedTotal">0</strong><span>files saved</span></div><div id="documentChecklist" class="dq-checklist"></div></section>
            <section class="dq-onboarding"><span class="dq-eyebrow">ONBOARDING</span><p id="onboardingStatus" role="status">Save or load an employee to check onboarding documents.</p><button type="button" id="completeOnboarding" onclick="completeEmployeeOnboarding()" disabled>Complete onboarding <span aria-hidden="true">→</span></button></section>
            <div class="dq-guidelines"><span class="dq-eyebrow">UPLOAD GUIDELINES</span><p>PDF, JPG, PNG or WebP</p><p>Up to 5 MB per file · 10 files per type</p><p>Expiry dates apply only to documents that expire.</p></div>
        </aside>
    </div>
</div>
<style>
.dq-workspace {
    --dq-bg:#ffffff;
    --dq-card:#ffffff;
    --dq-line:#e2e8f0;
    --dq-text:#1e293b;
    --dq-muted:#64748b;
    margin-top:0;
    background:var(--dq-bg);
    color:var(--dq-text);
    overflow:hidden;
    font-size:14px
}


.dq-header {
    padding:0 0 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    border-bottom:1px solid #f1f5f9;
    background:transparent
}


.dq-eyebrow {
    font-size:12px;
    letter-spacing:1.8px;
    font-weight:700;
    color:#64748b
}

.dq-header h2 {
    font-size:18px;
    line-height:28px;
    font-weight:700;
    color:#0f172a;
    margin:0
}

.dq-accent {
    color:#e11d48
}

.dq-header p {
    margin-top:4px;
    font-size:14px;
    line-height:20px;
    font-weight:400;
    color:#64748b
}

.dq-section-heading p {
    font-size:14px;
    color:var(--dq-muted);
    line-height:1.7
}

.dq-header-badge {
    display:flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border:1px solid #e2e8f0;
    border-radius:30px;
    font-size:12px;
    white-space:nowrap;
    color:#475569
}

.dq-header-badge>span {
    width:6px;
    height:6px;
    border-radius:50%;
    background:#059669
}


.dq-layout {
    display:grid;
    grid-template-columns:minmax(0,1fr) 260px
}

.dq-main {
    padding:28px 24px 28px 0;
    min-width:0
}

.dq-section-heading {
    display:flex;
    gap:12px;
    align-items:center;
    margin-bottom:26px
}

.dq-step {
    width:34px;
    height:34px;
    border-radius:10px;
    display:grid;
    place-items:center;
    background:#fff1f2;
    color:#be123c;
    font-size:14px;
    font-weight:700
}

.dq-section-heading h4 {
    font-size:16px;
    font-weight:650
}

.dq-categories {
    border:0;
    padding:0;
    margin:0 0 24px;
    min-width:0
}

.dq-category-options {
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:10px
}

.dq-categories legend {
    width:100%;
    font-size:14px;
    font-weight:600;
    margin-bottom:10px;
    color:#334155
}

.dq-categories label {
    position:relative;
    cursor:pointer;
    min-width:0
}

.dq-categories input {
    position:absolute;
    opacity:0;
    width:1px;
    height:1px
}

.dq-categories label>span {
    display:flex;
    flex-direction:column;
    min-height:112px;
    border:1px solid var(--dq-line);
    border-radius:12px;
    padding:14px 12px;
    transition:border-color .2s,background .2s
}

.dq-categories svg {
    height:21px;
    width:21px;
    color:#64748b;
    margin-bottom:12px
}

.dq-categories strong {
    font-size:14px;
    line-height:1.5;
    font-weight:600
}

.dq-categories small {
    font-size:12px;
    color:var(--dq-muted);
    margin-top:3px
}

.dq-categories input:checked+span {
    background:#fff1f2;
    border-color:#e11d48;
    box-shadow:inset 0 0 0 1px #e11d4810
}

.dq-categories input:checked+span svg {
    color:#be123c
}

.dq-categories input:focus-visible+span {
    outline:2px solid #be123c;
    outline-offset:3px
}

.dq-picker-label {
    display:block;
    font-size:14px;
    color:#334155;
    margin-bottom:8px;
    font-weight:600
}

.dq-picker-label>span {
    color:#be123c
}

.dq-workspace .dq-type-picker {
    width:100%;
    border:1px solid #cbd5e1;
    border-radius:10px;
    background-color:#ffffff;
    color:#1e293b;
    font-size:14px;
    padding:12px 36px 12px 14px;
    color-scheme:light
}


.dq-editor {
    margin-top:24px
}

.dq-editor[hidden],.dq-empty[hidden] {
    display:none!important
}

.dq-editor-heading {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:18px
}

.dq-type-title {
    font-size:14px;
    font-weight:600
}

.dq-mandatory {
    display:inline-block;
    font-size:12px;
    color:#9f1239;
    background:#ffe4e6;
    padding:3px 7px;
    border-radius:5px;
    margin-left:6px
}

.dq-count {
    font-size:12px;
    color:#475569;
    background:#f1f5f9;
    padding:5px 9px;
    border-radius:6px
}

.dq-workspace .dq-editor label:not(.dq-upload) {
    font-size:14px;
    color:#475569
}

.dq-workspace .dq-editor input:not([type=file]):not([type=checkbox]),.dq-workspace .dq-editor textarea {
    background:#ffffff;
    color:#1e293b;
    border:1px solid #cbd5e1;
    border-radius:10px;
    color-scheme:light;
    font-size:14px;
    box-shadow:none
}

.dq-workspace input::placeholder,.dq-workspace textarea::placeholder {
    color:#94a3b8
}

.dq-workspace input:focus,.dq-workspace textarea:focus,.dq-workspace select:focus {
    outline:2px solid #fb7185;
    outline-offset:2px;
    --tw-ring-shadow:0 0 #0000
}

.dq-workspace .dq-editor p {
    color:var(--dq-muted)
}


.dq-upload {
    position:relative;
    display:flex;
    align-items:center;
    flex-direction:column;
    gap:8px;
    margin-top:22px;
    padding:26px 20px;
    border:1px dashed #cbd5e1;
    border-radius:14px;
    background:linear-gradient(135deg,#f8fafc,#ffffff);
    cursor:pointer;
    text-align:center;
    transition:border-color .2s,background .2s
}

.dq-upload:hover,.dq-upload:focus-within {
    border-color:#be123c;
    background:#fff1f2
}

.dq-upload-icon {
    width:42px;
    height:42px;
    background:#ffe4e6;
    color:#be123c;
    border-radius:12px;
    padding:10px;
    margin-bottom:3px
}

.dq-upload strong {
    font-size:14px;
    font-weight:600
}

.dq-upload>span:not(.dq-upload-icon):not(.dq-upload-browse) {
    font-size:12px;
    color:var(--dq-muted)
}

.dq-upload-browse {
    font-size:12px;
    font-weight:600;
    border:1px solid #cbd5e1;
    border-radius:7px;
    padding:7px 13px;
    margin-top:5px
}

.dq-file-input {
    position:absolute;
    inset:0;
    opacity:0;
    width:100%;
    height:100%;
    cursor:pointer
}

.dq-saved-files {
    display:grid;
    gap:8px
}

.dq-workspace .dq-saved-file {
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    border:1px solid var(--dq-line);
    border-radius:10px;
    background:#f8fafc;
    padding:12px;
    font-size:14px
}

.dq-workspace .dq-saved-file>a {
    color:#334155;
    overflow-wrap:anywhere;
    min-width:0;
    text-decoration:underline;
    text-underline-offset:3px
}

.dq-workspace .dq-remove {
    display:flex;
    align-items:center;
    gap:5px;
    font-size:12px;
    color:#be123c!important
}

.dq-save-hint {
    display:flex;
    gap:5px;
    flex-wrap:wrap;
    border-top:1px solid var(--dq-line);
    margin-top:26px;
    padding-top:16px;
    font-size:12px;
    line-height:1.8;
    color:#64748b
}

.dq-save-hint>span {
    color:#be123c;
    margin-right:3px
}

.dq-save-hint strong {
    color:#334155;
    font-weight:500
}

.dq-aside {
    padding:28px 24px;
    background:#f8fafc;
    border-left:1px solid var(--dq-line)
}

.dq-summary h4 {
    font-size:21px;
    line-height:1.4;
    letter-spacing:-.4px;
    margin-top:12px;
    color:#0f172a
}

.dq-summary-number {
    display:flex;
    align-items:baseline;
    gap:9px;
    margin:20px 0
}

.dq-summary-number strong {
    font-size:36px;
    letter-spacing:-1px;
    font-weight:600
}

.dq-summary-number>span {
    font-size:14px;
    color:var(--dq-muted)
}

.dq-checklist {
    display:grid;
    gap:5px
}

.dq-checklist button {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    width:100%;
    text-align:left;
    padding:9px 10px;
    border-radius:8px;
    font-size:12px;
    color:#475569;
    border:1px solid transparent
}

.dq-checklist button:hover,.dq-checklist button[aria-pressed=true] {
    background:#ffffff;
    border-color:#cbd5e1;
    color:#0f172a
}

.dq-checklist button>span:last-child {
    font-size:12px;
    border:1px solid #e2e8f0;
    padding:2px 6px;
    border-radius:5px
}

.dq-onboarding {
    border-top:1px solid var(--dq-line);
    margin-top:24px;
    padding-top:22px
}

.dq-onboarding p {
    font-size:14px;
    line-height:1.8;
    color:#475569;
    margin:12px 0 15px
}

.dq-onboarding button {
    display:flex;
    justify-content:space-between;
    align-items:center;
    width:100%;
    border-radius:9px;
    padding:11px 12px;
    background:#e11d48;
    color:#ffffff;
    font-size:14px;
    font-weight:700
}

.dq-onboarding button:disabled {
    background:#e2e8f0;
    color:#64748b;
    cursor:not-allowed
}

.dq-guidelines {
    margin-top:24px;
    padding-top:20px;
    border-top:1px solid var(--dq-line)
}

.dq-guidelines p {
    font-size:12px;
    line-height:1.8;
    color:#64748b;
    margin-top:8px
}

.dq-empty {
    font-size:14px;
    padding:20px 0;
    color:#475569
}


@media(min-width:1400px) {
    .dq-layout {
    grid-template-columns:minmax(0,1fr) 300px
}


}

@media(max-width:1100px) {
    .dq-layout {
    grid-template-columns:1fr
}

.dq-aside {
    border-left:0;
    border-top:1px solid var(--dq-line);
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:24px
}

.dq-onboarding {
    margin:0;
    padding:0;
    border:0
}

.dq-guidelines {
    grid-column:1/-1;
    margin:0
}

.dq-summary h4 br {
    display:none
}


}

@media(max-width:600px) {
    .dq-main {
    padding:22px 0
}

.dq-aside {
    padding:22px 18px
}

.dq-header-badge {
    display:none
}

.dq-header h2 {
    font-size:18px
}

.dq-category-options {
    gap:6px
}

.dq-categories label>span {
    padding:12px 8px
}

.dq-categories small {
    display:none
}

.dq-categories strong {
    font-size:12px
}

.dq-aside {
    grid-template-columns:1fr
}

.dq-onboarding {
    border-top:1px solid var(--dq-line);
    padding-top:20px
}

.dq-editor-heading {
    align-items:flex-start
}

.dq-save-hint {
    display:block
}


}


</style>
