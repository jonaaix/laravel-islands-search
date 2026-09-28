import { ref } from 'vue';

function read(key) {
    try {
        const stored = JSON.parse(window.localStorage.getItem(key) ?? '[]');

        return Array.isArray(stored) ? stored : [];
    } catch {
        return [];
    }
}

function useBrowserRecentHits(key, limit) {
    const entries = ref(read(key));

    const hits = ref(entries.value.map((entry) => entry.hit));
    const icons = ref(Object.fromEntries(entries.value.filter((entry) => entry.icon).map((entry) => [entry.hit.icon, entry.icon])));

    function remember(hit, icon) {
        const next = [{ hit, icon: icon ?? null }, ...entries.value.filter((entry) => entry.hit.url !== hit.url)].slice(0, limit);

        entries.value = next;
        hits.value = next.map((entry) => entry.hit);

        try {
            window.localStorage.setItem(key, JSON.stringify(next));
        } catch {
            // Storage can be full or disabled; the search keeps working without recents.
        }
    }

    return { hits, icons, remember, load: () => {} };
}

function useServerRecentHits(url, limit) {
    const hits = ref([]);
    const icons = ref({});

    function load(recent) {
        hits.value = recent ?? [];
    }

    function remember(hit) {
        hits.value = [hit, ...hits.value.filter((entry) => entry.url !== hit.url)].slice(0, limit);

        // Sent while the browser is already leaving, so it has to outlive the page.
        fetch(url, {
            method: 'POST',
            keepalive: true,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify(hit),
        }).catch(() => {});
    }

    return { hits, icons, remember, load };
}

export function useRecentHits(key, limit, url = null) {
    return url ? useServerRecentHits(url, limit) : useBrowserRecentHits(key, limit);
}
