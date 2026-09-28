<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch\Tests\Fixtures;

use Aaix\LaravelIslandsSearch\Contracts\RecentHitStore;
use Aaix\LaravelIslandsSearch\SearchHit;
use Illuminate\Contracts\Auth\Authenticatable;

class ArrayRecentStore implements RecentHitStore
{
    /** @var array<int|string, list<SearchHit>> */
    public static array $hits = [];

    public function recent(Authenticatable $user, int $limit): array
    {
        return array_slice(self::$hits[$user->getAuthIdentifier()] ?? [], 0, $limit);
    }

    public function remember(Authenticatable $user, SearchHit $hit, int $limit): void
    {
        $kept = array_filter(self::$hits[$user->getAuthIdentifier()] ?? [], fn (SearchHit $known): bool => $known->url !== $hit->url);

        self::$hits[$user->getAuthIdentifier()] = array_slice([$hit, ...$kept], 0, $limit);
    }
}
