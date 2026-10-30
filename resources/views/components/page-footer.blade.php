<footer class="bg-white rounded-2xl border border-gray-100 shadow-xs py-4 px-6 mt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 transition-all duration-300">

    <!-- Left Section: Company & ERP Identity -->
    <div class="flex flex-wrap items-center gap-2 text-center sm:text-left">
        <span class="font-bold text-slate-900 text-xs sm:text-sm">
            {{ isset($companyDetails) ? ($companyDetails->company_name ?? 'Partner IT') : 'Partner IT' }} ERP Enterprise System
        </span>
        <span class="text-slate-300 hidden sm:inline">·</span>
        <span class="text-slate-400 text-xs font-normal">
            {{ isset($companyDetails) ? ($companyDetails->company_name ?? 'Partner IT') : 'Partner IT' }} Solutions &amp; Services (Pvt) Ltd.
        </span>
    </div>

    <!-- Right Section: Live Synchronized Server Time -->
    <div class="flex items-center gap-2">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-50 border border-slate-200/80 text-slate-600 font-mono-data text-[11px] font-semibold shadow-2xs">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 pulse-live"></span>
            <span>Server Time:</span>
            <span id="footer-server-time">{{ now()->format('m/d/Y, h:i:s A') }}</span>
        </div>
    </div>

</footer>

@once
<script>
    (() => {
        const clock = document.getElementById('footer-server-time');
        const serverTime = {{ now()->getTimestampMs() }};
        const startedAt = performance.now();
        const formatter = new Intl.DateTimeFormat('en-US', {
            timeZone: @json(config('app.timezone')),
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true,
        });
        const update = () => {
            clock.textContent = formatter.format(new Date(serverTime + performance.now() - startedAt));
        };
        update();
        setInterval(update, 1000);
    })();
</script>
@endonce
