<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class IslandsSearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/islands-search.php', 'islands-search');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'islands-search');

        Blade::componentNamespace('Aaix\\LaravelIslandsSearch\\View', 'islands-search');

        $this->publishes([
            __DIR__.'/../config/islands-search.php' => config_path('islands-search.php'),
        ], 'islands-search-config');
    }
}
