<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { navigateTo } from '@aaix/laravel-islands';
import { useIsland, useTranslations } from '@aaix/laravel-islands/vue';
import { Modal, TextField, provideIcons } from '@aaix/laravel-islands/vue/helpers';
import SearchResults from '../components/SearchResults.vue';
import SearchTips from '../components/SearchTips.vue';
import SearchTrigger from '../components/SearchTrigger.vue';
import { useRecentHits } from '../recentHits.js';

const { props } = useIsland();
const { t } = useTranslations();

const icons = reactive({ ...props.icons });
provideIcons(icons);

const recent = useRecentHits(props.recentKey, props.recentLimit);

const isOpen = ref(false);
const query = ref('');
const groups = ref([]);
const tips = ref([]);
const isLoading = ref(false);
const hasError = ref(false);
const activeIndex = ref(0);
const inputFrame = ref(null);

let controller = null;
let tipsRequested = false;
let debounce = null;

const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform);

const visibleGroups = computed(() => {
    if (query.value.trim() !== '') {
        return groups.value;
    }

    return recent.hits.value.length > 0 ? [{ key: 'recent', label: t('Recent'), hits: recent.hits.value }] : [];
});

const flatHits = computed(() => visibleGroups.value.flatMap((group) => group.hits));

function focusInput() {
    nextTick(() => requestAnimationFrame(() => inputFrame.value?.querySelector('input')?.focus()));
}

function open() {
    isOpen.value = true;
    activeIndex.value = 0;
    loadTips();
    Object.assign(icons, recent.icons.value);
    // The modal focuses its first control on open; the input takes focus after it.
    focusInput();
}

function close() {
    isOpen.value = false;
    controller?.abort();
}

async function search(term, signal) {
    const url = new URL(props.searchUrl, window.location.origin);
    url.searchParams.set('q', term);

    const response = await fetch(url, {
        credentials: 'same-origin',
        signal,
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });

    if (!response.ok) {
        throw new Error(`Search failed with status ${response.status}`);
    }

    return (await response.json()).data;
}

async function loadTips() {
    if (tipsRequested) {
        return;
    }

    tipsRequested = true;

    try {
        tips.value = (await search('')).tips;
    } catch {
        tipsRequested = false;
    }
}

function applyTip(example) {
    query.value = example;
    focusInput();
}

async function fetchHits(term) {
    controller?.abort();
    controller = new AbortController();
    isLoading.value = true;
    hasError.value = false;

    try {
        const data = await search(term, controller.signal);
        Object.assign(icons, data.icons);
        groups.value = data.groups;
        activeIndex.value = 0;
    } catch (error) {
        if (error.name !== 'AbortError') {
            hasError.value = true;
        }
    } finally {
        isLoading.value = false;
    }
}

watch(query, (term) => {
    clearTimeout(debounce);

    if (term.trim() === '') {
        controller?.abort();
        groups.value = [];
        isLoading.value = false;
        hasError.value = false;
        activeIndex.value = 0;

        return;
    }

    debounce = setTimeout(() => fetchHits(term.trim()), 150);
});

function visit(hit) {
    recent.remember(hit, icons[hit.icon]);
    close();
    navigateTo(hit.url);
}

function onKeydown(event) {
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();

        const count = flatHits.value.length;

        if (count > 0) {
            activeIndex.value = (activeIndex.value + (event.key === 'ArrowDown' ? 1 : -1) + count) % count;
        }
    }

    if (event.key === 'Enter' && flatHits.value[activeIndex.value]) {
        event.preventDefault();
        visit(flatHits.value[activeIndex.value]);
    }
}

function onGlobalKeydown(event) {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        isOpen.value ? close() : open();
    }
}

onMounted(() => window.addEventListener('keydown', onGlobalKeydown));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onGlobalKeydown);
    controller?.abort();
    clearTimeout(debounce);
});
</script>

<template>
    <div>
        <SearchTrigger :shortcut="isMac ? '⌘K' : 'Ctrl K'" :label="t('Search')" @open="open" />

        <Modal :open="isOpen" :title="t('Search')" size="md" :close-label="t('Close')" @close="close">
            <div class="-mt-2 space-y-3">
                <div ref="inputFrame" class="relative">
                    <TextField
                        :class="{ 'pe-11': tips.length > 0 }"
                        v-model="query"
                        size="lg"
                        autocomplete="off"
                        spellcheck="false"
                        enterkeyhint="search"
                        :placeholder="t('Search modules and records')"
                        :aria-label="t('Search')"
                        @keydown="onKeydown"
                    />
                    <SearchTips v-if="tips.length > 0" class="absolute inset-0 flex items-center justify-end pe-1.5" :tips="tips" @pick="applyTip" />
                </div>

                <SearchResults
                    :groups="visibleGroups"
                    :active-index="activeIndex"
                    :query="query.trim()"
                    :is-loading="isLoading"
                    :has-error="hasError"
                    @visit="visit"
                    @hover="(index) => (activeIndex = index)"
                    @retry="fetchHits(query.trim())"
                />
            </div>

            <template #footer>
                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span><kbd class="font-sans">↑↓</kbd> {{ t('to navigate') }}</span>
                    <span><kbd class="font-sans">↵</kbd> {{ t('to open') }}</span>
                    <span><kbd class="font-sans">esc</kbd> {{ t('to close') }}</span>
                </div>
            </template>
        </Modal>
    </div>
</template>
