<script setup>
import { computed } from 'vue';
import { useTranslations } from '@aaix/laravel-islands/vue';
import { Button, Icon, Skeleton } from '@aaix/laravel-islands/vue/helpers';

const props = defineProps({
    groups: { type: Array, required: true },
    activeIndex: { type: Number, required: true },
    query: { type: String, required: true },
    isLoading: { type: Boolean, default: false },
    hasError: { type: Boolean, default: false },
});

defineEmits(['visit', 'hover', 'retry']);

const { t } = useTranslations();

const indexedGroups = computed(() => {
    let index = 0;

    return props.groups.map((group) => ({
        ...group,
        hits: group.hits.map((hit) => ({ hit, index: index++ })),
    }));
});
</script>

<template>
    <div class="min-h-72">
        <div v-if="hasError" class="space-y-3 py-10 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('The search failed.') }}</p>
            <Button tone="secondary" @click="$emit('retry')">{{ t('Retry') }}</Button>
        </div>

        <div v-else-if="isLoading && groups.length === 0" class="space-y-2 pt-2">
            <Skeleton v-for="n in 5" :key="n" variant="block" height="2.25rem" />
        </div>

        <p v-else-if="query !== '' && groups.length === 0" class="py-10 text-center text-sm text-gray-600 dark:text-gray-400">
            {{ t('No results for ":query".', { query }) }}
        </p>

        <p v-else-if="groups.length === 0" class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ t('Type to search modules and records.') }}
        </p>

        <div v-else class="max-h-[60vh] space-y-3 overflow-y-auto" role="listbox">
            <section v-for="group in indexedGroups" :key="group.key">
                <h3 class="px-2 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ group.label }}</h3>
                <ul>
                    <li v-for="{ hit, index } in group.hits" :key="hit.url">
                        <a
                            :href="hit.url"
                            role="option"
                            :aria-selected="index === activeIndex"
                            class="flex h-11 items-center gap-3 rounded-lg px-2 text-sm"
                            :class="index === activeIndex ? 'bg-gray-100 text-gray-950 dark:bg-white/10 dark:text-white' : 'text-gray-700 dark:text-gray-300'"
                            @mousemove="$emit('hover', index)"
                            @click.prevent="$emit('visit', hit)"
                        >
                            <Icon v-if="hit.icon" :name="hit.icon" class="size-4 shrink-0 text-gray-400 dark:text-gray-500" />
                            <span class="min-w-0">
                                <span class="block font-medium">{{ hit.title }}</span>
                                <span v-if="hit.subtitle" class="block text-xs text-gray-500 dark:text-gray-400">{{ hit.subtitle }}</span>
                            </span>
                        </a>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
