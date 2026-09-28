<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch\Http;

use Aaix\LaravelIslandsSearch\Contracts\RecentHitStore;
use Aaix\LaravelIslandsSearch\SearchHit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

class RecentHitController extends Controller
{
    public const string STORE = 'islandsSearchRecentStore';

    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2000'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
            'kind' => ['nullable', 'string', 'max:60'],
            'data' => ['nullable', 'array'],
        ]);

        // A stored link is rendered as an anchor on the next visit; one leading elsewhere would turn the list into a way off the site.
        if (! $this->isInternal($validated['url'], $request)) {
            throw ValidationException::withMessages(['url' => __('The link must point inside this application.')]);
        }

        /** @var RecentHitStore $store */
        $store = app($request->route()?->defaults[self::STORE]);

        $store->remember($request->user(), new SearchHit(
            title: $validated['title'],
            url: $validated['url'],
            subtitle: $validated['subtitle'] ?? null,
            icon: $validated['icon'] ?? null,
            kind: $validated['kind'] ?? null,
            data: $validated['data'] ?? [],
        ), (int) config('islands-search.recent_limit'));

        return response()->noContent();
    }

    private function isInternal(string $url, Request $request): bool
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        return parse_url($url, PHP_URL_HOST) === $request->getHost()
            && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
    }
}
