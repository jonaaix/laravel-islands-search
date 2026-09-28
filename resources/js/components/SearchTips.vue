<script setup>
import { ref } from 'vue';
import { useTranslations } from '@aaix/laravel-islands/vue';
import { Icon, IconButton, Popover } from '@aaix/laravel-islands/vue/helpers';

defineProps({
    tips: { type: Array, required: true },
});

const emit = defineEmits(['pick']);

const { t } = useTranslations();

const anchor = ref(null);
const isOpen = ref(false);
const width = ref(320);

function toggle() {
    width.value = anchor.value?.offsetWidth ?? 320;
    isOpen.value = !isOpen.value;
}

function pick(tip) {
    isOpen.value = false;
    emit('pick', tip.example);
}
</script>

<template>
    <div ref="anchor" class="pointer-events-none">
        <IconButton class="pointer-events-auto" size="sm" :label="t('Search tips')" :aria-expanded="isOpen" @click="toggle">
            <Icon name="o-information-circle" />
        </IconButton>

        <Popover :anchor="anchor" :open="isOpen" :width="width" @close="isOpen = false">
            <div class="p-2">
                <h3 class="px-2 pb-1 pt-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ t('Search tips') }}</h3>
                <ul class="grid grid-cols-[max-content_1fr]">
                    <li v-for="tip in tips" :key="tip.example" class="col-span-2 grid grid-cols-subgrid">
                        <button
                            type="button"
                            class="col-span-2 grid min-h-9 grid-cols-subgrid items-baseline gap-x-4 rounded-lg px-2 py-2 text-start transition-colors hover:bg-gray-100 dark:hover:bg-white/10"
                            @click="pick(tip)"
                        >
                            <span class="font-mono text-sm font-medium text-gray-950 dark:text-white">{{ tip.example }}</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ tip.description }}</span>
                        </button>
                    </li>
                </ul>
            </div>
        </Popover>
    </div>
</template>
