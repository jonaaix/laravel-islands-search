<script setup>
import { Icon } from '@aaix/laravel-islands/vue/helpers';

defineProps({
    label: { type: String, required: true },
    shortcut: { type: String, required: true },
});

defineEmits(['open']);
</script>

<template>
    <button
        type="button"
        class="flex h-9 w-full items-center gap-2 rounded-lg bg-gray-50 px-3 text-sm text-gray-500 ring-1 ring-gray-200 transition-colors hover:bg-gray-100 max-sm:justify-center max-sm:px-0 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10 dark:hover:bg-white/10"
        :aria-label="label"
        @click="$emit('open')"
    >
        <Icon name="m-magnifying-glass" class="size-4 shrink-0" />
        <span class="max-sm:hidden">{{ label }}</span>
        <kbd class="ms-auto rounded-md px-1.5 py-0.5 font-sans text-xs text-gray-400 ring-1 ring-gray-200 max-sm:hidden dark:text-gray-500 dark:ring-white/10">{{ shortcut }}</kbd>
    </button>
</template>
