@if(auth()->user()?->canManageEmployeeDocuments() || auth()->user()?->canReviewBiometricEnrollments())
    <details id="alarmNotifications" class="erp-notifications">
        <summary id="alarmNotificationToggle" aria-label="Notifications" aria-controls="alarmNotificationPanel" aria-expanded="false" title="Notifications">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
            <span id="alarmNotificationCount" hidden></span>
        </summary>
        <section id="alarmNotificationPanel" aria-labelledby="alarmNotificationTitle">
            <header><div><h2 id="alarmNotificationTitle">Notifications</h2><p id="alarmNotificationSummary">Document alerts and enrollments awaiting review</p></div><button type="button" id="closeAlarmNotifications" aria-label="Close notifications">×</button></header>
            <p id="alarmNotificationStatus" role="status">Loading notifications…</p>
            <div id="alarmNotificationList"></div>
            <button type="button" id="moreAlarmNotifications" hidden>Load more</button>
        </section>
    </details>
    <style>
        .erp-notifications{position:relative;display:flex;align-items:center;flex:none;padding:0 16px;border-left:1px solid #303743;color:#cbd5e1}
        .erp-notifications>summary{position:relative;display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:9px;cursor:pointer;list-style:none}
        .erp-notifications>summary::-webkit-details-marker{display:none}.erp-notifications>summary:hover{background:#303744;color:#fff}
        .erp-notifications>summary:focus-visible{outline:2px solid #fda4af;outline-offset:2px}
        #alarmNotificationCount{position:absolute;top:-2px;right:-4px;min-width:18px;padding:2px 4px;border-radius:12px;background:#e11d48;color:#fff;font-size:10px;font-weight:700;text-align:center}
        #alarmNotificationPanel{position:fixed;top:calc(var(--erp-header-height) + 8px);right:16px;z-index:80;width:min(390px,calc(100vw - 24px));max-height:calc(100dvh - var(--erp-header-height) - 24px);overflow-y:auto;border:1px solid #e2e8f0;border-radius:14px;background:white;color:#1e293b;white-space:normal;box-shadow:0 12px 45px #0f172a30}
        #alarmNotificationPanel>header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:18px;border-bottom:1px solid #e2e8f0}
        #alarmNotificationTitle{font-size:15px;font-weight:700}#alarmNotificationSummary{font-size:11px;color:#64748b;margin-top:4px}
        #closeAlarmNotifications{font-size:23px;color:#64748b;line-height:1;padding:0 4px}
        #alarmNotificationStatus{padding:16px;font-size:12px;color:#64748b}
        .erp-alarm-item{border-bottom:1px solid #f1f5f9}.erp-alarm-item button{width:100%;padding:15px 18px;text-align:left;background:#fff;font-size:12px;line-height:1.6}
        .erp-alarm-item button:hover{background:#f8fafc}.erp-alarm-item button:focus-visible{outline:2px solid #94a3b8;outline-offset:-2px}
        .erp-alarm-item.is-unread button{background:#fff7f8}.erp-alarm-item strong{display:block;color:#0f172a;font-weight:650;overflow-wrap:anywhere}
        .erp-alarm-item p{color:#64748b;font-size:11px;overflow-wrap:anywhere}.erp-alarm-item .alarm-expired{color:#b91c1c}.erp-alarm-item .alarm-expiring{color:#92400e}
        #moreAlarmNotifications{width:100%;padding:13px;font-size:12px;font-weight:600;color:#475569}
        @media(max-width:700px){.erp-notifications{padding:0 8px}#alarmNotificationPanel{right:12px}}
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dropdown = document.getElementById('alarmNotifications');
            const toggle = document.getElementById('alarmNotificationToggle');
            const count = document.getElementById('alarmNotificationCount');
            const list = document.getElementById('alarmNotificationList');
            const status = document.getElementById('alarmNotificationStatus');
            const more = document.getElementById('moreAlarmNotifications');
            const endpoint = @json(route('hr.person.notifications.index'));
            let nextPage = null;
            let loading = false;
            async function loadNotifications(append = false) {
                if (loading) return;
                const url = append ? nextPage : endpoint;
                if (!url) return;
                loading = true;
                more.disabled = true;
                try {
                    const response = await fetch(url, {headers: {'Accept': 'application/json'}, cache: 'no-store'});
                    if (!response.ok) throw new Error('Unable to load notifications. Close and reopen to try again.');
                    const data = await response.json();
                    count.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    count.hidden = !data.unread_count;
                    toggle.setAttribute('aria-label', `Notifications: ${data.unread_count} unread`);
                    document.getElementById('alarmNotificationSummary').textContent = `${data.unread_count} unread · ${data.total} active notifications`;
                    if (!append) list.replaceChildren();
                    for (const item of data.notifications) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = item.open_url;
                        form.className = 'erp-alarm-item' + (item.is_read ? '' : ' is-unread');
                        const csrf = document.createElement('input');
                        csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = document.querySelector('meta[name="csrf-token"]').content;
                        const button = document.createElement('button');
                        button.type = 'submit';
                        const title = document.createElement('strong');
                        title.textContent = item.type === 'enrollment'
                            ? `${item.is_read ? '' : '● '}Pending enrollment · ${item.name}`
                            : `${item.is_read ? '' : '● '}${item.document_type} · ${item.employee_name}`;
                        const details = document.createElement('p');
                        details.textContent = item.type === 'enrollment'
                            ? `${item.enrollment_id} · ${item.device} · ${item.branch}`
                            : `${item.employee_id} · ${item.file_name}`;
                        const expiry = document.createElement('p');
                        expiry.className = item.type === 'enrollment' ? 'alarm-expiring' : (item.status === 'expired' ? 'alarm-expired' : 'alarm-expiring');
                        expiry.textContent = item.type === 'enrollment'
                            ? 'Pending HR Review → Review enrollment'
                            : `${item.status === 'expired' ? 'Expired' : 'Expiring soon'} · ${item.due_on} → Review document`;
                        button.append(title, details, expiry);
                        form.append(csrf, button); list.append(form);
                    }
                    status.textContent = data.total ? '' : 'No active notifications.';
                    status.hidden = Boolean(data.total);
                    nextPage = data.next_page_url;
                    more.hidden = !nextPage;
                } catch (error) {
                    status.hidden = false; status.textContent = error.message;
                } finally {loading = false; more.disabled = false;}
            }
            dropdown.addEventListener('toggle', () => {
                toggle.setAttribute('aria-expanded', String(dropdown.open));
                if (dropdown.open) loadNotifications();
            });
            document.getElementById('closeAlarmNotifications').addEventListener('click', () => {dropdown.open = false; toggle.focus();});
            more.addEventListener('click', () => loadNotifications(true));
            document.addEventListener('click', event => {if (!dropdown.contains(event.target)) dropdown.open = false;});
            document.addEventListener('keydown', event => {if (event.key === 'Escape' && dropdown.open) {dropdown.open = false; toggle.focus();}});
            loadNotifications();
            setInterval(() => {if (!document.hidden && !dropdown.open) loadNotifications();}, 60000);
            if (new URL(window.location.href).searchParams.get('notifications') === '1') dropdown.open = true;
        });
    </script>
@endif
