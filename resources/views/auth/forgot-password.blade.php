<x-guest-layout title="Forgot Password | HR & Payroll — Partner IT ERP">
    <div class="flex flex-col lg:flex-row min-h-screen bg-slate-50">
        <!-- Left Section — Brand Experience -->
        <x-auth-brand-panel
            tag="ACCOUNT RECOVERY"
            title="Secure Account Recovery."
            subtitle="Follow our verified recovery procedure to securely reset your credentials and restore workspace access."
            :features="[
                [
                    'title' => 'Identity Verification',
                    'desc' => 'We verify your registered corporate email to protect your account against unauthorized access.',
                    'icon' => 'shield'
                ],
                [
                    'title' => 'One-Time Security Token',
                    'desc' => 'A time-sensitive, single-use encrypted reset token is dispatched directly to your inbox.',
                    'icon' => 'lock'
                ],
                [
                    'title' => 'Seamless Restoration',
                    'desc' => 'Set your new password and return to your workforce & payroll management portal instantly.',
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
                            ACCOUNT RECOVERY
                        </span>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            Forgot your password?
                        </h1>
                        <p class="mt-1.5 text-xs sm:text-sm text-slate-500 leading-relaxed">
                            No problem. Enter your corporate email address and we will dispatch a secure link to reset your credentials.
                        </p>
                    </div>

                    <!-- Session Status -->
                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <!-- Form Validation Alert -->
                    @if ($errors->any())
                        <div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50/90 p-4 text-sm text-red-800 shadow-xs">
                            <div class="flex items-center gap-2 font-semibold text-red-900">
                                <svg class="w-4 h-4 text-[#C81E24] shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <span>Reset request failed</span>
                            </div>
                            <div class="mt-1 text-xs text-red-700 pl-6">{{ $errors->first() }}</div>
                        </div>
                    @endif

                    <!-- Form -->
                    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="_redirect_after_reset" value="1">

                        <!-- Email Address Field -->
                        <div>
                            <x-input-label for="email" :value="__('Email address')" class="block text-xs font-semibold text-slate-700 mb-1.5" />
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                                    </svg>
                                </div>
                                <input id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="name@company.com"
                                    class="block w-full pl-10 pr-3.5 py-2.5 text-sm text-slate-900 bg-white border border-slate-300 rounded-xl shadow-xs placeholder:text-slate-400 focus:outline-none focus:border-[#C81E24] focus:ring-4 focus:ring-red-500/10 transition-all duration-150" />
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                        </div>

                        <!-- Action Button -->
                        <div class="pt-2">
                            <button type="submit"
                                class="w-full h-11 inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-[#C81E24] hover:bg-[#A8171C] active:bg-[#911317] border border-transparent rounded-xl font-semibold text-sm text-white shadow-md shadow-red-700/20 hover:shadow-lg hover:shadow-red-700/30 focus:outline-none focus:ring-4 focus:ring-red-500/20 transition-all duration-150 cursor-pointer active:scale-[0.99]">
                                <span>Email Password Reset Link</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </button>
                        </div>

                        <!-- Back to Login -->
                        <div class="text-center pt-4 border-t border-slate-100">
                            <a class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-[#C81E24] transition-colors focus:outline-none focus:underline" href="{{ route('login.form') }}">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                <span>{{ __('Back to login') }}</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Page Footer -->
            <footer class="text-center text-xs text-slate-400 py-3">
                &copy; {{ date('Y') }} Partner IT ERP. All rights reserved.
            </footer>
        </div>
    </div>
</x-guest-layout>
