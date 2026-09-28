<p align="center">
  <a href="https://github.com/jonaaix/laravel-islands-search">
    <img src="https://raw.githubusercontent.com/jonaaix/laravel-islands-search/main/laravel-islands-search.svg" alt="Laravel Islands Search Logo" width="200">
  </a>
</p>

<h1 align="center">Laravel Islands Search</h1>

<p align="center">
A keyboard-driven global search for <a href="https://github.com/jonaaix/laravel-islands">Laravel Islands</a>: one modal, any number of sources, grouped hits, recent hits and search tips — with an optional Filament panel integration.
</p>

<p align="center">
  <a href="https://packagist.org/packages/aaix/laravel-islands-search"><img src="https://img.shields.io/packagist/v/aaix/laravel-islands-search.svg?style=flat-square" alt="Latest Version on Packagist"></a>
  <a href="https://packagist.org/packages/aaix/laravel-islands-search"><img src="https://img.shields.io/packagist/dt/aaix/laravel-islands-search.svg?style=flat-square" alt="Total Downloads"></a>
</p>

---

The package ships a search trigger with a <kbd>⌘K</kbd> / <kbd>Ctrl K</kbd> shortcut, a modal with
arrow-key navigation and one JSON endpoint. What can be found is up to you: every *source* is a
small class that answers a query with hits. The package groups them, renders them and remembers
what was opened.

- **Sources** — plain classes, or a Scout model in a few lines
- **Own rows** — hand a result kind your own Vue component; the rest keep the built-in row
- **Leading sources** — a source may move to the top for the queries it owns
- **Recent hits** — kept in the browser, or on the server through a store you provide
- **Search tips** — sources explain their shorthands in a popover next to the field
- **Filament** — one plugin call replaces the panel's global search

## Installation

```bash
composer require aaix/laravel-islands-search
```

Add the Vite plugin — it registers the import name `@aaix/laravel-islands-search` and, when the
package is installed from a Composer path repository, points it at the working copy:

```js
// vite.config.js
import islandsSearch from './vendor/aaix/laravel-islands-search/vite.js';

export default defineConfig({
    plugins: [/* … */ islandsSearch()],
});
```

Register the package's island next to your own and let Tailwind see its classes:

```js
// resources/js/app.js
import islands from '@aaix/laravel-islands/islands';
import { startVueIslands } from '@aaix/laravel-islands/vue';
import { searchIslands } from '@aaix/laravel-islands-search';

startVueIslands({ ...islands, ...searchIslands });
```

```css
@source '../../vendor/aaix/laravel-islands-search/resources/**/*';
```

Optionally publish the configuration:

```bash
php artisan vendor:publish --tag=islands-search-config
```

## Filament

The plugin switches Filament's own global search off, registers the endpoint inside the panel and
puts the trigger into the topbar:

```php
use Aaix\LaravelIslandsSearch\Filament\IslandsSearchPlugin;

$panel->plugins([
    IslandsSearchPlugin::make()
        ->sources([
            PageSource::class,
            OrderSource::class,
        ])
        ->recentStore(AccountRecentHits::class), // optional, see "Recent hits"
]);
```

## Without Filament

Register the endpoint behind your own authentication and place the trigger wherever the search
belongs:

```php
use Aaix\LaravelIslandsSearch\IslandsSearch;

Route::middleware(['web', 'auth'])->group(function () {
    IslandsSearch::route('search', [PageSource::class, OrderSource::class])->name('search');
});
```

```blade
<x-islands-search::search-trigger :url="route('search')" />
```

Guests are rejected: sources only ever see an authenticated user.

## Writing a source

A source names itself, decides who may see it and answers a query:

```php
use Aaix\LaravelIslandsSearch\Contracts\SearchSource;
use Aaix\LaravelIslandsSearch\SearchHit;
use Illuminate\Contracts\Auth\Authenticatable;

class PageSource implements SearchSource
{
    public function key(): string
    {
        return 'pages';
    }

    public function label(): string
    {
        return __('Pages');
    }

    public function isVisibleTo(Authenticatable $user): bool
    {
        return true;
    }

    public function search(string $query, int $limit): array
    {
        return collect(config('navigation.pages'))
            ->filter(fn (array $page): bool => str_contains(mb_strtolower($page['label']), mb_strtolower($query)))
            ->take($limit)
            ->map(fn (array $page): SearchHit => new SearchHit(
                title: $page['label'],
                url: $page['url'],
                subtitle: $page['section'],
                icon: 'o-document-text',
            ))
            ->values()
            ->all();
    }
}
```

Groups appear in the order the sources are registered; a source without hits is left out. `icon`
is a Heroicon name (`o-…` outline, `s-…` solid, `m-…` mini) — the endpoint sends the SVG along,
nothing has to be bundled.

For a model that is already searchable with Laravel Scout, extend `ScoutSource`:

```php
use Aaix\LaravelIslandsSearch\Sources\ScoutSource;

class CustomerSource extends ScoutSource
{
    public function key(): string { return 'customers'; }

    public function label(): string { return __('Customers'); }

    public function isVisibleTo(Authenticatable $user): bool
    {
        return $user->can('viewAny', Customer::class);
    }

    protected function model(): string
    {
        return Customer::class;
    }

    protected function toHit(Model $record): SearchHit
    {
        return new SearchHit($record->name, route('customers.show', $record), $record->email, 'o-user');
    }
}
```

### Search tips

A source implementing `ProvidesSearchTips` explains its shorthands. The tips appear in a popover
beside the field; picking one types its example into the search.

```php
public function tips(): array
{
    return [new SearchTip('#1734', __('Finds the record with that ID'))];
}
```

### Leading for a query

Some queries clearly belong to one source — four digits read off a printed order, an email
address. A source implementing `LeadsForQuery` moves to the top for those; the others keep their
order.

```php
public function leadsFor(string $query): bool
{
    return preg_match('/^\d{4}$/', $query) === 1;
}
```

## Your own rows

The built-in row shows an icon, a title and a subtitle. When a kind of result needs more — a photo,
a status, a price — give the hit a `kind` and the `data` your row needs:

```php
new SearchHit(
    title: $order->number,
    url: route('orders.show', $order),
    subtitle: $order->customer_name,
    kind: 'order',
    data: ['number' => $order->number, 'customer' => $order->customer_name, 'total' => $order->total_label],
);
```

Then register a component for that kind once, before the islands start:

```js
import { registerSearchRows, searchIslands } from '@aaix/laravel-islands-search';
import OrderRow from './search/OrderRow.vue';

registerSearchRows({ order: OrderRow });
startVueIslands({ ...islands, ...searchIslands });
```

The search keeps the keyboard, the active state, the recent list and the navigation; the row only
draws. It receives three props and emits one event:

```vue
<script setup>
const props = defineProps({
    hit: { type: Object, required: true },     // the whole hit; your fields are in hit.data
    query: { type: String, default: '' },      // empty while the row sits in the recent list
    active: { type: Boolean, default: false }, // keyboard or pointer is on this row
});

const emit = defineEmits(['visit']);
</script>

<template>
    <div
        class="flex items-center gap-3 rounded-lg px-2 py-2"
        :class="active ? 'bg-gray-100 dark:bg-white/10' : ''"
    >
        <a :href="hit.url" class="font-medium" @click.prevent="emit('visit')">{{ hit.data.number }}</a>
        <span class="text-sm text-gray-500 dark:text-gray-400">{{ hit.data.customer }}</span>
        <span class="ms-auto tabular-nums">{{ hit.data.total }}</span>
    </div>
</template>
```

Three things to keep in mind:

- The search wraps every row in its own `<li>` — the row's root element must not be one.
- Emit `visit` instead of navigating yourself, or the hit never reaches the recent list.
- The modal provides only the icons its hits name. A row using other icons provides them itself
  (`provideIcons` from `@aaix/laravel-islands/vue/helpers`).

A kind without a registered row falls back to the built-in one.

## Recent hits

Without further setup the modal keeps the last opened hits per user in the browser's
`localStorage`. To keep them on the account instead — the same list on every device — give the
plugin a store:

```php
use Aaix\LaravelIslandsSearch\Contracts\RecentHitStore;
use Aaix\LaravelIslandsSearch\SearchHit;

class AccountRecentHits implements RecentHitStore
{
    public function recent(Authenticatable $user, int $limit): array
    {
        return collect($user->settings['recent_hits'] ?? [])
            ->take($limit)
            ->map(fn (array $hit): SearchHit => new SearchHit(...$hit))
            ->all();
    }

    public function remember(Authenticatable $user, SearchHit $hit, int $limit): void
    {
        $kept = collect($user->settings['recent_hits'] ?? [])->reject(fn (array $known): bool => $known['url'] === $hit->url);

        $user->settings = [...$user->settings, 'recent_hits' => $kept->prepend($hit->toArray())->take($limit)->values()->all()];
        $user->save();
    }
}
```

The endpoint then answers an empty query with the stored list, and the modal posts every opened
hit back. Posted links must point inside the application — anything else is refused, because a
stored link is rendered as an anchor on the next visit.

A stored hit is a snapshot. `recent()` is asked on every opening, so a store may redraw hits whose
state moves on — an order's status, say — before handing them out.

Outside Filament, register the store on the endpoint and add the route that receives opened hits:

```php
IslandsSearch::route('search', $sources, AccountRecentHits::class)->name('search');
IslandsSearch::recentRoute('search/recent', AccountRecentHits::class)->name('search.recent');
```

```blade
<x-islands-search::search-trigger :url="route('search')" :recent-url="route('search.recent')" />
```

## Configuration

| Key | Default | |
| --- | --- | --- |
| `limit_per_source` | `6` | Hits a single source may contribute to one answer |
| `max_query_length` | `100` | Longer queries are rejected before a source sees them |
| `recent_limit` | `8` | Recent hits kept per user |

## Styling the trigger

The trigger button carries the class `islands-search-trigger` (its placeholder too, so nothing jumps
while the island mounts). Style it from the host to match your application's chrome:

```css
.fi-topbar .islands-search-trigger {
    border-radius: 9999px;
}
```

## License

[MIT](LICENSE.md)
