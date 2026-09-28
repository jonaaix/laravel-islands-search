const rows = {};

export function registerSearchRows(components) {
    Object.assign(rows, components);
}

export function searchRowFor(hit) {
    return hit.kind ? rows[hit.kind] ?? null : null;
}
