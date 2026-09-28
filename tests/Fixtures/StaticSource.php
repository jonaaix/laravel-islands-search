<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch\Tests\Fixtures;

use Aaix\LaravelIslandsSearch\Contracts\ProvidesSearchTips;
use Aaix\LaravelIslandsSearch\Contracts\SearchSource;
use Aaix\LaravelIslandsSearch\SearchHit;
use Aaix\LaravelIslandsSearch\SearchTip;
use Illuminate\Contracts\Auth\Authenticatable;

class StaticSource implements ProvidesSearchTips, SearchSource
{
    public function key(): string
    {
        return 'pages';
    }

    public function label(): string
    {
        return 'Pages';
    }

    public function isVisibleTo(Authenticatable $user): bool
    {
        return true;
    }

    public function search(string $query, int $limit): array
    {
        return collect(['Orders', 'Order returns', 'Invoices', 'Offers', 'Ordering rules', 'Orbit', 'Ore', 'Oregano'])
            ->filter(fn (string $title): bool => str_contains(strtolower($title), strtolower($query)))
            ->take($limit)
            ->map(fn (string $title): SearchHit => new SearchHit($title, '/'.strtolower($title), icon: 'o-document'))
            ->values()
            ->all();
    }

    public function tips(): array
    {
        return [new SearchTip('orders', 'Finds the orders page')];
    }
}
