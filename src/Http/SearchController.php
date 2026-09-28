<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch\Http;

use Aaix\LaravelIslandsSearch\Contracts\ProvidesSearchTips;
use Aaix\LaravelIslandsSearch\Contracts\SearchSource;
use Aaix\LaravelIslandsSearch\SearchHit;
use Aaix\LaravelIslandsSearch\SearchTip;
use Aaix\LaravelIslandsSearch\Support\HeroiconSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SearchController extends Controller
{
    public const string SOURCES = 'islandsSearchSources';

    public function __invoke(Request $request, HeroiconSet $icons): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:'.(int) config('islands-search.max_query_length')],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));

        $sources = $this->visibleSources($request);

        $groups = $query === '' ? [] : $this->groups($sources, $query);

        $iconNames = collect($groups)->flatMap(fn (array $group): array => array_column($group['hits'], 'icon'))->filter()->values()->all();

        return response()->json(['data' => [
            'query' => $query,
            'groups' => $groups,
            'icons' => $icons->build($iconNames),
            'tips' => $query === '' ? $this->tips($sources) : [],
        ]]);
    }

    /**
     * @param  list<SearchSource>  $sources
     * @return list<array{key: string, label: string, hits: list<array<string, ?string>>}>
     */
    private function groups(array $sources, string $query): array
    {
        $limit = (int) config('islands-search.limit_per_source');

        return collect($sources)
            ->map(fn (SearchSource $source): array => [
                'key' => $source->key(),
                'label' => $source->label(),
                'hits' => array_map(fn (SearchHit $hit): array => $hit->toArray(), $source->search($query, $limit)),
            ])
            ->filter(fn (array $group): bool => $group['hits'] !== [])
            ->values()
            ->all();
    }

    /**
     * @param  list<SearchSource>  $sources
     * @return list<array{example: string, description: string}>
     */
    private function tips(array $sources): array
    {
        return collect($sources)
            ->filter(fn (SearchSource $source): bool => $source instanceof ProvidesSearchTips)
            ->flatMap(fn (ProvidesSearchTips $source): array => array_map(fn (SearchTip $tip): array => $tip->toArray(), $source->tips()))
            ->values()
            ->all();
    }

    /**
     * @return list<SearchSource>
     */
    private function visibleSources(Request $request): array
    {
        /** @var list<class-string<SearchSource>> $classes */
        $classes = $request->route()?->defaults[self::SOURCES] ?? [];

        return collect($classes)
            ->map(fn (string $source): SearchSource => app($source))
            ->filter(fn (SearchSource $source): bool => $request->user() !== null && $source->isVisibleTo($request->user()))
            ->values()
            ->all();
    }
}
