<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch;

use Aaix\LaravelIslandsSearch\Contracts\RecentHitStore;
use Aaix\LaravelIslandsSearch\Contracts\SearchSource;
use Aaix\LaravelIslandsSearch\Http\RecentHitController;
use Aaix\LaravelIslandsSearch\Http\SearchController;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

class IslandsSearch
{
    /**
     * @param  list<class-string<SearchSource>>  $sources
     * @param  class-string<RecentHitStore>|null  $recentStore
     */
    public static function route(string $uri, array $sources, ?string $recentStore = null): RoutingRoute
    {
        return Route::get($uri, SearchController::class)
            ->defaults(SearchController::SOURCES, $sources)
            ->defaults(RecentHitController::STORE, $recentStore);
    }

    /**
     * @param  class-string<RecentHitStore>  $recentStore
     */
    public static function recentRoute(string $uri, string $recentStore): RoutingRoute
    {
        return Route::post($uri, RecentHitController::class)->defaults(RecentHitController::STORE, $recentStore);
    }
}
