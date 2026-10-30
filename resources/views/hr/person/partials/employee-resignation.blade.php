@if(auth()->user()?->role === 'admin')
    <div class="mt-8 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div><h3 class="text-sm font-semibold text-slate-900">Employment status <span id="basicEmploymentStatus" class="ml-2 rounded-full bg-white px-2 py-1 text-xs text-slate-600">New employee</span></h3><p id="resignationHint" class="mt-2 text-xs text-slate-500">Load an employee to record their resignation.</p></div>
        <button type="button" id="openResignation" onclick="openEmployeeResignation()" disabled class="rounded-lg border border-rose-200 bg-white px-4 py-2 text-xs font-semibold text-rose-700 disabled:opacity-40">Mark as resigned</button>
    </div>
    <dialog aria-labelledby="employeeResignationTitle" id="employeeResignationDialog" class="w-full max-w-lg rounded-2xl border-0 p-0 shadow-xl backdrop:bg-slate-900/40">
        <form id="employeeResignationForm" onsubmit="submitEmployeeResignation(event)" class="p-6">
            <h3 id="employeeResignationTitle" class="text-lg font-bold text-slate-900">Record employee resignation</h3>
            <p id="resigningEmployeeName" class="mt-2 text-sm font-medium text-slate-700"></p>
            <p class="mt-2 text-xs leading-5 text-slate-500">The employee will be marked as Resigned in the directory and listed under Resigned Employees. Their records and documents will be retained.</p>
            <label for="employeeResignedOn" class="mt-5 block text-sm font-medium text-slate-700">Resignation date</label>
            <input id="employeeResignedOn" type="date" aria-describedby="employeeResignationDateHint" required max="{{ now('Asia/Colombo')->toDateString() }}" class="mt-2 w-full rounded-lg border-slate-300 text-sm">
            <p id="employeeResignationDateHint" class="mt-2 text-xs text-slate-500">Select a date between the employment date and today.</p>
            <label for="employeeResignationReason" class="mt-4 block text-sm font-medium text-slate-700">Reason for resignation</label>
            <textarea id="employeeResignationReason" required maxlength="2000" rows="3" class="mt-2 w-full rounded-lg border-slate-300 text-sm" placeholder="Enter the reason for leaving"></textarea>
            <p id="employeeResignationError" role="alert" class="mt-3 text-xs text-red-700"></p>
            <div class="mt-5 flex justify-end gap-2"><button type="button" id="cancelResignation" onclick="document.getElementById('employeeResignationDialog').close()" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600">Cancel</button><button id="confirmResignation" class="erp-primary-action rounded-lg px-4 py-2 text-sm font-semibold text-white">Confirm resignation</button></div>
        </form>
    </dialog>
    <style>
        #employeeResignationDialog {
            position: fixed;
            inset: 0;
            margin: auto;
            width: min(32rem, calc(100vw - 32px));
            max-width: none;
            max-height: calc(100dvh - 32px);
            overflow-y: auto;
            background: #fff;
            color: #0f172a;
        }
        #employeeResignedOn,
        #employeeResignationReason {
            display: block;
            width: 100%;
            min-width: 0;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px 14px;
            background: #fff;
            color: #0f172a;
            font: inherit;
            font-size: 14px;
            line-height: 1.5;
            color-scheme: light;
        }
        #employeeResignedOn { min-height: 46px; }
        #employeeResignedOn::-webkit-calendar-picker-indicator { cursor: pointer; }
        #employeeResignationReason { resize: vertical; min-height: 100px; }
        #employeeResignedOn:focus,
        #employeeResignationReason:focus {
            outline: none;
            box-shadow: none;
            border-color: #64748b;
        }
    </style>
@endif
