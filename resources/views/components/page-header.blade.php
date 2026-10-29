@props([
    'overline' => null,
    'title' => '',
    'badge' => null,
    'description' => null,
    'stats' => null,
])

<div {{ $attributes->merge(['class' => 'relative mb-6 overflow-hidden rounded-2xl border border-gray-900 bg-gradient-to-r from-gray-950 via-gray-900 to-red-950 p-6 text-white shadow-md sm:p-7']) }}>
    <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-red-700/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 left-1/3 h-64 w-64 rounded-full bg-red-900/10 blur-2xl"></div>

    <div class="relative z-10 flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
        <div class="flex items-start gap-4 sm:items-center">
            @isset($icon)
                <div class="mt-0.5 flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-red-800/40 bg-gradient-to-br from-red-900/80 to-gray-900 text-white shadow-inner sm:mt-0 sm:h-14 sm:w-14">
                    {{ $icon }}
                </div>
            @endisset

            <div class="space-y-1">
                @if ($overline)
                    <div class="flex items-center gap-2 text-[11px] font-black uppercase tracking-widest text-red-400">
                        <span class="animate-pulse h-1.5 w-1.5 rounded-full bg-red-500"></span>
                        <span>{{ $overline }}</span>
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">
                        {{ $title }}
                    </h1>
                    @if ($badge)
                        <span class="inline-flex items-center rounded-full border border-emerald-800/50 bg-emerald-950/60 px-2.5 py-0.5 font-mono text-xs font-semibold text-emerald-400">
                            {{ $badge }}
                        </span>
                    @endif
                </div>

                @if ($description)
                    <p class="max-w-2xl text-xs font-normal leading-relaxed text-gray-300 sm:text-sm">
                        {{ $description }}
                    </p>
                @endif
            </div>
        </div>

        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2 self-start lg:self-auto">
                {{ $actions }}
            </div>
        @endisset
    </div>

    @if ($stats)
        <div class="relative z-10 mt-5 flex flex-wrap items-center gap-4 border-t border-gray-800/60 pt-4 text-xs font-medium text-gray-400 sm:gap-6">
            {{ $stats }}
        </div>
    @endif
</div>

<x-flash-message />
