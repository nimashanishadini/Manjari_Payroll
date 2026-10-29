@props([
    'tag' => 'ENTERPRISE WORKFORCE SUITE',
    'title' => 'Simplify Your Workforce Management.',
    'subtitle' => 'Manage employees, payroll, attendance, and HR operations from one secure platform.',
    'features' => [],
    'footer' => 'Built for smarter business operations.'
])

<div class="hidden lg:flex lg:w-[45%] xl:w-[42%] 2xl:w-[40%] flex-col justify-between p-10 xl:p-14 bg-gradient-to-br from-[#101828] via-[#0D1524] to-[#070C16] text-white relative overflow-hidden shrink-0 border-r border-slate-800/80 shadow-2xl">
    <!-- Subtle Abstract Geometric Background Patterns -->
    <div class="absolute inset-0 pointer-events-none opacity-[0.035]" aria-hidden="true">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
            <defs>
                <pattern id="auth-grid-pattern" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="1" />
                    <circle cx="40" cy="40" r="1.5" fill="currentColor" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#auth-grid-pattern)" />
        </svg>
    </div>

    <!-- Soft Glow Accents -->
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-red-600/10 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-blue-600/5 rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>

    <!-- Top Section: Brand Identity -->
    <div class="relative z-10">
        <a href="/" class="inline-flex items-center gap-3.5 group focus:outline-none focus:ring-2 focus:ring-red-500 rounded-xl p-1 transition-transform">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#C81E24] via-red-700 to-[#8E1116] flex items-center justify-center text-white shadow-lg shadow-red-950/60 border border-red-500/30 group-hover:scale-105 transition-all duration-200">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div class="text-left">
                <span class="block text-xs font-bold uppercase tracking-widest text-red-400">Partner IT ERP</span>
                <span class="block text-lg font-bold text-white tracking-tight leading-tight">HR &amp; Payroll Management</span>
            </div>
        </a>
    </div>

    <!-- Middle Section: Content & Feature Highlights -->
    <div class="relative z-10 my-auto py-8">
        <!-- Tagline Pill with subtle pulse -->
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-950/50 border border-red-500/30 text-red-300 text-[11px] font-semibold tracking-wider uppercase mb-5 shadow-xs">
            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
            {{ $tag }}
        </div>

        <!-- Headline & Accent Line -->
        <h2 class="text-3xl xl:text-4xl font-extrabold text-white tracking-tight leading-[1.15]">
            {{ $title }}
        </h2>
        
        <div class="w-12 h-1 bg-gradient-to-r from-[#C81E24] to-red-400 rounded-full my-4"></div>

        <p class="text-slate-300 text-sm leading-relaxed max-w-lg mb-8">
            {{ $subtitle }}
        </p>

        <!-- Compact Feature Cards -->
        <div class="space-y-3.5 max-w-lg">
            @forelse ($features as $feature)
                <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-md hover:border-slate-700/90 transition-all duration-200">
                    <div class="w-9 h-9 rounded-lg bg-red-950/50 border border-red-900/60 flex items-center justify-center text-red-400 shrink-0 mt-0.5 shadow-xs">
                        @if (isset($feature['icon']) && $feature['icon'] === 'users')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        @elseif (isset($feature['icon']) && $feature['icon'] === 'payroll')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @elseif (isset($feature['icon']) && $feature['icon'] === 'clock')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @elseif (isset($feature['icon']) && $feature['icon'] === 'shield')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        @elseif (isset($feature['icon']) && $feature['icon'] === 'lock')
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        @else
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white tracking-tight">{{ $feature['title'] }}</h3>
                        <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">{{ $feature['desc'] }}</p>
                    </div>
                </div>
            @empty
                <!-- Fallback defaults -->
                <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-slate-900/60 border border-slate-800/80">
                    <div class="w-9 h-9 rounded-lg bg-red-950/50 border border-red-900/60 flex items-center justify-center text-red-400 shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Employee Management</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Comprehensive profiles, department structures, and career records.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Bottom Section: Corporate Footer -->
    <div class="relative z-10 pt-6 border-t border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-400">
        <p class="font-medium text-slate-300">{{ $footer }}</p>
        <span class="inline-flex items-center gap-1.5 text-[11px] text-slate-500">
            <svg class="w-3.5 h-3.5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            256-Bit SSL Encrypted
        </span>
    </div>
</div>
