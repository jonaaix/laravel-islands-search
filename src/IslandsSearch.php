<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch;

use Aaix\LaravelIslandsSearch\Contracts\SearchSource;
use Aaix\LaravelIslandsSearch\Http\SearchController;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

class IslandsSearch
{
    /**
     * @param  list<class-string<SearchSource>>  $sources
     */
    public static function route(string $uri, array $sources): RoutingRoute
    {
        return Route::get($uri, SearchController::class)->defaults(SearchController::SOURCES, $sources);
    }
}
