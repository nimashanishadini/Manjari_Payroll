<x-guest-layout title="Register | HR & Payroll — Partner IT ERP">
    <div class="flex flex-col lg:flex-row min-h-screen bg-slate-50">
        <!-- Left Section — Brand Experience -->
        <x-auth-brand-panel
            tag="ENTERPRISE WORKFORCE ONBOARDING"
            title="Build a Better Workplace."
            subtitle="Get started with a streamlined platform for workforce and payroll management."
            :features="[
                [
                    'title' => 'Automated Payroll Cycles',
                    'desc' => 'Precision salary calculations, customized deductions, and automated payslip generation.',
                    'icon' => 'payroll'
                ],
                [
                    'title' => 'Real-time Attendance & Leave',
                    'desc' => 'Biometric clock-in sync, dynamic shift scheduling, and frictionless approval workflows.',
                    'icon' => 'clock'
                ],
                [
                    'title' => 'Role-based Access Control',
                    'desc' => 'Strict privilege segregation ensuring sensitive employee and compensation data stays secure.',
                    'icon' => 'shield'
                ]
            ]"
            footer="Built for smarter business operations."
        />

        <!-- Right Section — Registration Form Area -->
        <div class="flex-1 flex flex-col justify-between p-4 sm:p-8 lg:p-12 xl:p-16 overflow-y-auto">
            <!-- Mobile Brand Header (Visible only on mobile/tablet < lg) -->
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
            <div class="my-auto mx-auto w-full max-w-lg py-4">
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xl shadow-slate-200/60 p-6 sm:p-9 transition-all">
                    <!-- Header -->
                    <div class="mb-6">
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-[#C81E24] bg-red-50 border border-red-100 rounded-md px-2 py-0.5 mb-2">
                            GET STARTED
                        </span>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            Create your account
                        </h1>
                        <p class="mt-1.5 text-xs sm:text-sm text-slate-500 leading-relaxed">
                            Set up your account to get started with workforce and payroll management.
                        </p>
                    </div>

                    <!-- Form-level Validation Alert -->
                    @if ($errors->any())
                        <div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50/90 p-4 text-sm text-red-800 shadow-xs">
                            <div class="flex items-center gap-2 font-semibold text-red-900">
                                <svg class="w-4 h-4 text-[#C81E24] shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <span>Registration could not be completed</span>
                            </div>
                            <div class="mt-1 text-xs text-red-700 pl-6">{{ $errors->first() }}</div>
                        </div>
                    @endif

                    <!-- Registration Form -->
                    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ showPassword: false }">
                        @csrf
                        <input type="hidden" name="_redirect_after_register" value="1">

                        <!-- Full Name -->
                        <div>
                            <x-input-label for="name" :value="__('Full name')" class="block text-xs font-semibold text-slate-700 mb-1.5" />
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <input id="name"
                                    type="text"
                                    name="name"
                                    value="{{ old('name') }}"
                                    required
                                    autofocus
                                    autocomplete="name"
                                    placeholder="e.g. John Doe"
                                    class="block w-full pl-10 pr-3.5 py-2.5 text-sm text-slate-900 bg-white border border-slate-300 rounded-xl shadow-xs placeholder:text-slate-400 focus:outline-none focus:border-[#C81E24] focus:ring-4 focus:ring-red-500/10 transition-all duration-150" />
                            </div>
                            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                        </div>

                        <!-- Email Address -->
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
                                    autocomplete="username"
                                    placeholder="name@company.com"
                                    class="block w-full pl-10 pr-3.5 py-2.5 text-sm text-slate-900 bg-white border border-slate-300 rounded-xl shadow-xs placeholder:text-slate-400 focus:outline-none focus:border-[#C81E24] focus:ring-4 focus:ring-red-500/10 transition-all duration-150" />
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                        </div>

                        <!-- Responsive 2-column grid on desktop for passwords -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                                        autocomplete="new-password"
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

                            <!-- Confirm Password -->
                            <div>
                                <x-input-label for="password_confirmation" :value="__('Confirm password')" class="block text-xs font-semibold text-slate-700 mb-1.5" />
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </div>
                                    <input id="password_confirmation"
                                        :type="showPassword ? 'text' : 'password'"
                                        name="password_confirmation"
                                        required
                                        autocomplete="new-password"
                                        placeholder="••••••••"
                                        class="block w-full pl-10 pr-3.5 py-2.5 text-sm text-slate-900 bg-white border border-slate-300 rounded-xl shadow-xs placeholder:text-slate-400 focus:outline-none focus:border-[#C81E24] focus:ring-4 focus:ring-red-500/10 transition-all duration-150">
                                </div>
                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
                            </div>
                        </div>

                        <!-- Password requirement hint -->
                        <div class="text-[11px] text-slate-500 bg-slate-50 border border-slate-200/80 rounded-lg p-2.5 flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            <span>Password must be at least 8 characters long and match the confirmation.</span>
                        </div>

                        <!-- Primary Action Button -->
                        <div class="pt-2">
                            <button type="submit"
                                class="w-full h-11 inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-[#C81E24] hover:bg-[#A8171C] active:bg-[#911317] border border-transparent rounded-xl font-semibold text-sm text-white shadow-md shadow-red-700/20 hover:shadow-lg hover:shadow-red-700/30 focus:outline-none focus:ring-4 focus:ring-red-500/20 transition-all duration-150 cursor-pointer active:scale-[0.99]">
                                <span>Create Account</span>
                                <span class="sr-only">Register Account</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>

                        <!-- Bottom Link -->
                        <div class="text-center pt-4 border-t border-slate-100">
                            <p class="text-xs text-slate-500">
                                {{ __('Already registered?') }}
                                <a class="font-semibold text-[#C81E24] hover:text-[#A8171C] transition-colors focus:outline-none focus:underline" href="{{ route('login.form') }}">
                                    {{ __('Log in here') }}
                                </a>
                            </p>
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
