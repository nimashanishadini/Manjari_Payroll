<x-guest-layout title="Confirm Password | HR & Payroll — Partner IT ERP">
    <div class="flex flex-col lg:flex-row min-h-screen bg-slate-50">
        <!-- Left Section — Brand Experience -->
        <x-auth-brand-panel
            tag="SECURITY VERIFICATION"
            title="Confirm Secure Access."
            subtitle="This is a privileged area of the HR & Payroll portal. Please verify your identity by confirming your password."
            :features="[
                [
                    'title' => 'Privileged Action Verification',
                    'desc' => 'High-sensitivity operations require re-verification of identity to safeguard employee data.',
                    'icon' => 'shield'
                ],
                [
                    'title' => 'Session Integrity',
                    'desc' => 'Confirmation protects against unauthorized physical access to active workstations.',
                    'icon' => 'lock'
                ],
                [
                    'title' => 'Audit Trail Compliance',
                    'desc' => 'Privileged confirmations are logged according to corporate security compliance standards.',
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
                            AUTHENTICATION CHECK
                        </span>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            Confirm your password
                        </h1>
                        <p class="mt-1.5 text-xs sm:text-sm text-slate-500 leading-relaxed">
                            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4" x-data="{ showPassword: false }">
                        @csrf

                        <!-- Password -->
                        <div>
                            <x-input-label for="password" :value="__('Password')" class="block text-xs font-semibold text-slate-700 mb-1.5" />
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="block w-full pl-10 pr-10 py-2.5 text-sm text-slate-900 bg-white border border-slate-300 rounded-xl shadow-xs placeholder:text-slate-400 focus:outline-none focus:border-[#C81E24] focus:ring-4 focus:ring-red-500/10 transition-all duration-150">

                                <button type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 focus:outline-none focus:text-slate-600 cursor-pointer"
                                    aria-label="Toggle password visibility">
                                    <svg x-show="!showPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                        </div>

                        <!-- Action Button -->
                        <div class="pt-2">
                            <button type="submit"
                                class="w-full h-11 inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-[#C81E24] hover:bg-[#A8171C] active:bg-[#911317] border border-transparent rounded-xl font-semibold text-sm text-white shadow-md shadow-red-700/20 hover:shadow-lg hover:shadow-red-700/30 focus:outline-none focus:ring-4 focus:ring-red-500/20 transition-all duration-150 cursor-pointer active:scale-[0.99]">
                                <span>{{ __('Confirm') }}</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
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
