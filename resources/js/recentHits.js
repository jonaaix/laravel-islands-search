import { ref } from 'vue';

function read(key) {
    try {
        const stored = JSON.parse(window.localStorage.getItem(key) ?? '[]');

        return Array.isArray(stored) ? stored : [];
    } catch {
        return [];
    }
}

export function useRecentHits(key, limit) {
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

    return { hits, icons, remember };
}
