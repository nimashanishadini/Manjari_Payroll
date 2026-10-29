<x-guest-layout title="Verify Email | HR & Payroll — Partner IT ERP">
    <div class="flex flex-col lg:flex-row min-h-screen bg-slate-50">
        <!-- Left Section — Brand Experience -->
        <x-auth-brand-panel
            tag="EMAIL VERIFICATION"
            title="Verify Your Identity."
            subtitle="Confirm your official email address to unlock your complete HR & Payroll workspace."
            :features="[
                [
                    'title' => 'Identity Confirmation',
                    'desc' => 'Email verification safeguards company payroll information and corporate assets.',
                    'icon' => 'shield'
                ],
                [
                    'title' => 'Direct Delivery',
                    'desc' => 'A unique cryptographic link has been dispatched directly to your inbox.',
                    'icon' => 'lock'
                ],
                [
                    'title' => 'Instant Portal Access',
                    'desc' => 'Clicking the link will automatically authenticate and unlock your employee account.',
                    'icon' => 'clock'
                ]
            ]"
            footer="Built for smarter business operations."
        />

        <!-- Right Section — Form Area -->
        <div class="flex-1 flex flex-col justify-between p-4 sm:p-8 lg:p-12 xl:p-16 overflow-y-auto">
            <!-- Mobile Brand Header -->
            <div class="lg:hidden flex items-center justify-between pb-5 mb-4 border-b border-slate-200/80">
                <a href="/" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#C81E24] via-red-700 to-[#8E1116] flex items-center justify-center text-white shadow-md shadow-red-900/30">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-[10px] font-bold uppercase tracking-widest text-[#C81E24]">Partner IT ERP</span>
                        <span class="block text-base font-bold text-slate-900 tracking-tight leading-tight">HR &amp; Payroll Management</span>
                    </div>
                </a>
            </div>

            <!-- Centered Form Container -->
            <div class="my-auto mx-auto w-full max-w-md py-4">
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xl shadow-slate-200/60 p-6 sm:p-9 transition-all">
                    <!-- Header -->
                    <div class="mb-6">
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-[#C81E24] bg-red-50 border border-red-100 rounded-md px-2 py-0.5 mb-2">
                            VERIFICATION REQUIRED
                        </span>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            Verify your email
                        </h1>
                        <p class="mt-1.5 text-xs sm:text-sm text-slate-500 leading-relaxed">
                            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
                        </p>
                    </div>

                    @if (session('status') == 'verification-link-sent')
                        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50/90 p-4 text-xs font-medium text-emerald-800 shadow-xs flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ __('A new verification link has been sent to the email address you provided during registration.') }}</span>
                        </div>
                    @endif

                    <div class="space-y-4">
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit"
                                class="w-full h-11 inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-[#C81E24] hover:bg-[#A8171C] active:bg-[#911317] border border-transparent rounded-xl font-semibold text-sm text-white shadow-md shadow-red-700/20 hover:shadow-lg hover:shadow-red-700/30 focus:outline-none focus:ring-4 focus:ring-red-500/20 transition-all duration-150 cursor-pointer active:scale-[0.99]">
                                <span>{{ __('Resend Verification Email') }}</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('logout') }}" class="text-center pt-2">
                            @csrf
                            <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-[#C81E24] transition-colors focus:outline-none focus:underline cursor-pointer">
                                {{ __('Log Out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Page Footer -->
            <footer class="text-center text-xs text-slate-400 py-3">
                &copy; {{ date('Y') }} Partner IT ERP. All rights reserved.
            </footer>
        </div>
    </div>
</x-guest-layout>
