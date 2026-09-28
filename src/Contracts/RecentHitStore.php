<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch\Contracts;

use Aaix\LaravelIslandsSearch\SearchHit;
use Illuminate\Contracts\Auth\Authenticatable;

interface RecentHitStore
{
    /**
     * @return list<SearchHit>
     */
    public function recent(Authenticatable $user, int $limit): array;

    public function remember(Authenticatable $user, SearchHit $hit, int $limit): void;
}
