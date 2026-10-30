@if(auth()->user()?->canManageEmployeeDocuments())
    <dialog id="documentTypeSettingsDialog" aria-labelledby="documentTypeSettingsTitle" class="backdrop:bg-slate-900/40">
        <header class="dt-dialog-header">
            <div><p>EMPLOYEE DOCUMENTS / CONFIGURATION</p><h2 id="documentTypeSettingsTitle">Manage document types</h2><span>Configure onboarding requirements and expiry reminders.</span></div>
            <button type="button" onclick="document.getElementById('documentTypeSettingsDialog').close()" aria-label="Close document type settings">×</button>
        </header>
        <div class="dt-dialog-body">
            <p class="dt-dialog-note">These settings apply to all employees. Save any employee changes first; updating a document type refreshes this page.</p>
            @include('hr.person.partials.document-type-settings', ['types' => $documentTypes, 'registrationContext' => true])
        </div>
        <footer class="dt-dialog-footer"><button type="button" onclick="document.getElementById('documentTypeSettingsDialog').close()">Done</button></footer>
    </dialog>
    <style>
        #documentTypeSettingsDialog{position:fixed;inset:0;margin:auto;width:min(760px,calc(100vw - 32px));max-width:none;max-height:calc(100dvh - 40px);padding:0;border:1px solid #e2e8f0;border-radius:16px;overflow-y:auto;background:#fff;color:#0f172a;box-shadow:0 24px 70px #0f172a40}
        .dt-dialog-header{position:sticky;top:0;z-index:2;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:24px 28px;border-bottom:1px solid #e2e8f0;background:#fff}.dt-dialog-header p{font-size:10px;font-weight:700;letter-spacing:1.4px;color:#64748b}.dt-dialog-header h2{font-size:20px;font-weight:700;line-height:1.4;margin:6px 0}.dt-dialog-header span{font-size:13px;line-height:1.6;color:#64748b}.dt-dialog-header>button{display:grid;place-items:center;width:32px;height:32px;flex:none;border:1px solid #e2e8f0;border-radius:8px;font-size:23px;line-height:1;color:#64748b}.dt-dialog-header>button:hover{background:#f1f5f9}.dt-dialog-body{padding:22px 28px 28px}.dt-dialog-note{padding:12px 14px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;font-size:12px;line-height:1.7;color:#64748b}.dt-dialog-footer{position:sticky;bottom:0;display:flex;justify-content:flex-end;padding:14px 28px;background:#fff;border-top:1px solid #e2e8f0}.dt-dialog-footer>button{padding:9px 22px;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;font-weight:600;color:#475569}
        @media(max-width:600px){.dt-dialog-header{padding:20px}.dt-dialog-body{padding:16px}.dt-dialog-footer{padding:12px 20px}.dt-dialog-header h2{font-size:18px}}
    </style>
@endif
