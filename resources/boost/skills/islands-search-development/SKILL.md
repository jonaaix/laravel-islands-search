---
name: islands-search-development
description: Extend the global search built on aaix/laravel-islands-search — search sources, search tips, a source leading for its queries, own result rows per kind and the recent-hits store. Use whenever something should become findable in the search modal, a result needs its own look, or the search's endpoint, plugin or recent list is touched.
---

# Extending the Global Search

The package owns the modal, the keyboard, the endpoint and the recent list. The host owns
**sources** (what can be found), optionally **rows** (how a kind of hit looks) and optionally a
**recent store** (where opened hits are kept). Find where the host registers its sources —
`IslandsSearchPlugin::make()->sources([...])` in a Filament panel provider, or
`IslandsSearch::route(...)` in a routes file — before adding anything.

## A source

```php
use Aaix\LaravelIslandsSearch\Contracts\SearchSource;
use Aaix\LaravelIslandsSearch\SearchHit;
use Illuminate\Contracts\Auth\Authenticatable;

final class InvoiceSearchSource implements SearchSource
{
    public function key(): string { return 'invoices'; }          // stable group key

    public function label(): string { return __('Invoices'); }    // group heading

    public function isVisibleTo(Authenticatable $user): bool
    {
        return $user->can('viewAny', Invoice::class);
    }

    public function search(string $query, int $limit): array
    {
        return Invoice::query()
            ->where('number', 'like', '%'.$query.'%')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Invoice $invoice): SearchHit => new SearchHit(
                title: $invoice->number,
                url: route('invoices.show', $invoice),
                subtitle: $invoice->customer_name,
                icon: 'o-document-text',
            ))
            ->all();
    }
}
```

- `isVisibleTo` is the authorisation — the endpoint is reachable by every signed-in user. Never
  return records the list view would hide from them.
- Reuse the query the list view searches with, so the modal and the list agree on what matches.
- Honour `$limit` (`islands-search.limit_per_source`) in the query itself; never fetch every record and slice.
- `icon` is a Heroicon name (`o-`, `s-`, `m-`). The endpoint ships the SVG.
- Groups follow registration order. A Scout-searchable model can extend `ScoutSource` and
  implement only `model()` and `toHit()`.

## Tips and leading

- `ProvidesSearchTips::tips()` returns `SearchTip(example, description)` for every shorthand the
  source understands (`#1734`, an invoice prefix). Descriptions go through `__()`.
- `LeadsForQuery::leadsFor(string $query): bool` moves the source's group to the top when the
  query clearly belongs to it. Keep the rule narrow — a source leading for everything reorders
  every answer.

## An own row for a kind

Only when the built-in row (icon, title, subtitle) cannot show what matters. Server side, give the
hit a `kind` and exactly the `data` the row reads — never a whole presenter payload, it travels on
every keystroke and into the recent list:

```php
new SearchHit(title: $invoice->number, url: $url, subtitle: $invoice->customer_name,
    kind: 'invoice', data: ['number' => $invoice->number, 'total' => $invoice->total_label]);
```

Client side, register the component once before `startVueIslands`:

```js
import { registerSearchRows, searchIslands } from '@aaix/laravel-islands-search';

registerSearchRows({ invoice: InvoiceResultRow });
startVueIslands({ ...islands, ...searchIslands });
```

The row contract:

- props `hit` (fields in `hit.data`), `query`, `active`; emits `visit`.
- Root element is not an `<li>` — the search wraps each row in one.
- Emit `visit` on click (`@click.prevent`); never set `window.location`. The search records the
  hit and navigates.
- Paint `active` yourself; keyboard selection is the search's.
- The modal provides only the icons its hits name. A row with other icons calls
  `provideIcons(...)` itself.
- `query === ''` means the row is drawn in the recent list. Hide figures that go stale there
  (stock, price) unless the recent store redraws them.

## Recent hits

Default: per user in `localStorage`. With `->recentStore(Store::class)` on the plugin (or
`IslandsSearch::route($uri, $sources, Store::class)` plus `IslandsSearch::recentRoute(...)`), the
list lives on the server:

- `RecentHitStore::recent($user, $limit)` is called on every opening of the modal — redraw hits
  whose state moves on (statuses, names) here instead of trusting the snapshot.
- `remember($user, $hit, $limit)` receives validated hits; the endpoint already refuses links
  leading off the application. Dedupe by `url`, newest first, cut at `$limit`.

## Testing

Hit the endpoint as a user and read `data.groups` — `[{key, label, hits: [{title, url, subtitle,
icon, kind, data}]}]`; an empty query answers with `data.tips` and `data.recent` instead. Cover
what the source finds, what it hides from a user without access, and — for a leading source —
the group order.
