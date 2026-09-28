<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { navigateTo } from '@aaix/laravel-islands';
import { useIsland, useTranslations } from '@aaix/laravel-islands/vue';
import { Button, Modal, Tabs, TextField, provideIcons } from '@aaix/laravel-islands/vue/helpers';
import SearchResults from '../components/SearchResults.vue';
import SearchTips from '../components/SearchTips.vue';
import SearchTrigger from '../components/SearchTrigger.vue';
import { useRecentHits } from '../recentHits.js';

const { props } = useIsland();
const { t } = useTranslations();

const icons = reactive({ ...props.icons });
provideIcons(icons);

const recent = useRecentHits(props.recentKey, props.recentLimit, props.recentUrl ?? null);

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

const ALL = '';
const activeGroup = ref(ALL);
const activeLabel = ref('');

const hasQuery = computed(() => query.value.trim() !== '');
const total = computed(() => groups.value.reduce((sum, group) => sum + group.hits.length, 0));

// A source that stops answering loses its group, so the chosen tab stays in place, empty, until left.
const missingGroup = computed(() => (hasQuery.value && activeGroup.value !== ALL && !groups.value.some((group) => group.key === activeGroup.value)
    ? { key: activeGroup.value, label: activeLabel.value }
    : null));

const tabs = computed(() => [
    { key: ALL, label: t('All'), count: total.value },
    ...groups.value.map((group) => ({ key: group.key, label: group.label, count: group.hits.length })),
    ...(missingGroup.value ? [{ ...missingGroup.value, count: 0 }] : []),
]);

const showsTabs = computed(() => hasQuery.value && (groups.value.length > 1 || activeGroup.value !== ALL));

const visibleGroups = computed(() => {
    if (hasQuery.value) {
        return activeGroup.value === ALL ? groups.value : groups.value.filter((group) => group.key === activeGroup.value);
    }

    return recent.hits.value.length > 0 ? [{ key: 'recent', label: t('Recent'), hits: recent.hits.value }] : [];
});

function selectGroup(key) {
    activeGroup.value = key;
    activeLabel.value = tabs.value.find((tab) => tab.key === key)?.label ?? '';
    activeIndex.value = 0;
    focusInput();
}

const flatHits = computed(() => visibleGroups.value.flatMap((group) => group.hits));

function focusInput() {
    nextTick(() => requestAnimationFrame(() => inputFrame.value?.querySelector('input')?.focus()));
}

function open() {
    isOpen.value = true;
    activeIndex.value = 0;
    activeGroup.value = ALL;
    loadIdle();
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

// Recent hits kept on the server are asked for on every opening: a record may have moved on since.
async function loadIdle() {
    if (tipsRequested && !props.recentUrl) {
        return;
    }

    tipsRequested = true;

    try {
        const data = await search('');
        tips.value = data.tips;
        Object.assign(icons, data.icons);
        recent.load(data.recent);
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

    if ((event.key === 'ArrowRight' || event.key === 'ArrowLeft') && showsTabs.value) {
        const keys = tabs.value.filter((tab) => tab.count > 0 || tab.key === activeGroup.value).map((tab) => tab.key);
        const at = keys.indexOf(activeGroup.value);

        if (keys.length > 1 && at !== -1) {
            event.preventDefault();
            selectGroup(keys[(at + (event.key === 'ArrowRight' ? 1 : -1) + keys.length) % keys.length]);
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

                <Tabs v-if="showsTabs" :model-value="activeGroup" :items="tabs" @update:model-value="selectGroup" />

                <div v-if="missingGroup" class="space-y-3 py-10 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ t('No :group for ":query".', { group: missingGroup.label, query: query.trim() }) }}</p>
                    <Button v-if="total > 0" tone="secondary" @click="selectGroup(ALL)">{{ t('Show all :count results', { count: total }) }}</Button>
                </div>

                <SearchResults
                    v-else
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
                    <span v-if="showsTabs"><kbd class="font-sans">←→</kbd> {{ t('to filter') }}</span>
                    <span><kbd class="font-sans">↵</kbd> {{ t('to open') }}</span>
                    <span><kbd class="font-sans">esc</kbd> {{ t('to close') }}</span>
                </div>
            </template>
        </Modal>
    </div>
</template>
