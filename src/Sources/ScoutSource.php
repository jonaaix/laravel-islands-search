<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch\Sources;

use Aaix\LaravelIslandsSearch\Contracts\SearchSource;
use Aaix\LaravelIslandsSearch\SearchHit;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
abstract class ScoutSource implements SearchSource
{
    /**
     * @return class-string<TModel>
     */
    abstract protected function model(): string;

    /**
     * @param  TModel  $record
     */
    abstract protected function toHit(Model $record): SearchHit;

    public function search(string $query, int $limit): array
    {
        return $this->model()::search($query)
            ->take($limit)
            ->get()
            ->map(fn (Model $record): SearchHit => $this->toHit($record))
            ->values()
            ->all();
    }
}
