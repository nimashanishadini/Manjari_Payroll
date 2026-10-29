@if (session('success') || session('error') || session('status'))
    @php
        $message = session('success') ?? session('error') ?? session('status');
        $isError = session('error');
    @endphp

    <div
        role="status"
        class="mb-6 rounded-xl border px-4 py-3 text-sm {{ $isError ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800' }}"
    >
        {{ $message }}
    </div>
@endif
