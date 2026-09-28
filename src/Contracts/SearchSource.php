<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch\Contracts;

use Aaix\LaravelIslandsSearch\SearchHit;
use Illuminate\Contracts\Auth\Authenticatable;

interface SearchSource
{
    public function key(): string;

    public function label(): string;

    public function isVisibleTo(Authenticatable $user): bool;

    /**
     * @return list<SearchHit>
     */
    public function search(string $query, int $limit): array;
}
