<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch\Tests;

use Aaix\LaravelIslands\IslandsServiceProvider;
use Aaix\LaravelIslandsSearch\IslandsSearch;
use Aaix\LaravelIslandsSearch\IslandsSearchServiceProvider;
use Aaix\LaravelIslandsSearch\Tests\Fixtures\AdminUsersSource;
use Aaix\LaravelIslandsSearch\Tests\Fixtures\ArrayRecentStore;
use Aaix\LaravelIslandsSearch\Tests\Fixtures\StaticSource;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Scout\ScoutServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            ScoutServiceProvider::class,
            IslandsServiceProvider::class,
            IslandsSearchServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('scout.driver', 'collection');
        $app['config']->set('auth.providers.users.model', Fixtures\User::class);
    }

    protected function defineRoutes($router): void
    {
        $router->middleware(['web', 'auth'])->group(function (): void {
            IslandsSearch::route('search', [StaticSource::class, AdminUsersSource::class])->name('search');
            IslandsSearch::route('remembering-search', [StaticSource::class], ArrayRecentStore::class)->name('remembering-search');
            IslandsSearch::recentRoute('remembering-search/recent', ArrayRecentStore::class)->name('remembering-search.recent');
        });
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
    }
}
