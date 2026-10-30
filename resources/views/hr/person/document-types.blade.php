<x-layouts.app title="Document Types" section="person">
    <section class="mx-auto max-w-6xl p-6 lg:p-10">
        <p class="text-xs font-bold uppercase tracking-widest text-rose-600">Person / Configuration</p>
        <h1 class="mt-2 text-3xl font-bold text-slate-900">Document Types</h1>
        <p class="mt-2 text-sm text-slate-500">Choose the copies employees need. Mandatory types must have a valid file before onboarding can be completed.</p>
        @include('hr.person.partials.document-type-settings')
    </section>
</x-layouts.app>
