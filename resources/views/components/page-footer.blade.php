<footer class="mt-6 flex flex-col items-center justify-between gap-3 rounded-2xl border border-gray-100 bg-white px-6 py-4 text-xs text-slate-500 shadow-sm transition-all duration-300 sm:flex-row">
    <div class="flex flex-wrap items-center gap-2 text-center sm:text-left">
        <span class="text-xs font-bold text-slate-900 sm:text-sm">
            {{ isset($companyDetails) ? ($companyDetails->company_name ?? 'Partner IT') : 'Partner IT' }} ERP Enterprise System
        </span>
        <span class="hidden text-slate-300 sm:inline">·</span>
        <span class="text-xs font-normal text-slate-400">
            {{ isset($companyDetails) ? ($companyDetails->company_name ?? 'Partner IT') : 'Partner IT' }} Solutions &amp; Services (Pvt) Ltd.
        </span>
    </div>

    <div class="flex items-center gap-2">
        <div class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 bg-slate-50 px-3 py-1 font-mono text-[11px] font-semibold text-slate-600 shadow-sm">
            <span class="animate-pulse h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
            <span>Server Time:</span>
            <span
                id="footer-server-time"
                x-data
                x-init="
                    const updateTime = () => {
                        const now = new Date();
                        const options = {
                            year: 'numeric', month: '2-digit', day: '2-digit',
                            hour: '2-digit', minute: '2-digit', second: '2-digit',
                            hour12: true
                        };
                        $el.innerText = now.toLocaleString('en-US', options);
                    };
                    updateTime();
                    setInterval(updateTime, 1000);
                "
            >{{ now()->format('m/d/Y, h:i:s A') }}</span>
        </div>
    </div>
</footer>
