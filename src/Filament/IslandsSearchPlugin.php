<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch\Filament;

use Aaix\LaravelIslandsSearch\Contracts\SearchSource;
use Aaix\LaravelIslandsSearch\IslandsSearch;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class IslandsSearchPlugin implements Plugin
{
    public const string ID = 'islands-search';

    /** @var list<class-string<SearchSource>> */
    private array $sources = [];

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return self::ID;
    }

    /**
     * @param  list<class-string<SearchSource>>  $sources
     */
    public function sources(array $sources): static
    {
        $this->sources = $sources;

        return $this;
    }

    public function register(Panel $panel): void
    {
        $panel
            ->globalSearch(false)
            ->authenticatedTenantRoutes(function (): void {
                IslandsSearch::route('islands-search', $this->sources)->name('islands-search');
            })
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn (): View => view('islands-search::filament.trigger', [
                'url' => Filament::getCurrentOrDefaultPanel()->route('islands-search', Filament::getTenant() === null ? [] : ['tenant' => Filament::getTenant()]),
                'recentKey' => implode(':', array_filter(['islands-search:recent', Filament::getId(), Filament::getTenant()?->getKey(), Auth::id()], fn ($part): bool => $part !== null)),
            ]));
    }

    public function boot(Panel $panel): void {}
}
