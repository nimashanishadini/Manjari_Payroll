<x-layouts.app title="Profile | HR & Payroll">
    <section class="mx-auto max-w-7xl space-y-6">
        <x-page-header
            overline="Account Settings"
            title="Profile"
            description="Manage your profile information and account security."
        />

        <div class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </section>
</x-layouts.app>
