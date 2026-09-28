{{-- Holds the trigger's place until the island has mounted, so the host never shows an empty gap. --}}
<style>[data-island="GlobalSearch"]:not(:empty) ~ .islands-search-placeholder { display: none; }</style>
<div {{ $attributes->class(['w-80 max-sm:w-9 lg:w-96']) }}>
    <x-island name="GlobalSearch" :props="$islandProps()" />
    <div
        aria-hidden="true"
        class="islands-search-placeholder flex h-9 w-full items-center gap-2 rounded-lg bg-gray-50 px-3 text-sm text-gray-500 ring-1 ring-gray-200 max-sm:justify-center max-sm:px-0 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10"
    >
        <x-heroicon-m-magnifying-glass class="size-4 shrink-0" />
        <span class="max-sm:hidden">{{ __('Search') }}</span>
        <kbd class="invisible ms-auto rounded-md px-1.5 py-0.5 font-sans text-xs max-sm:hidden">Ctrl K</kbd>
    </div>
</div>
