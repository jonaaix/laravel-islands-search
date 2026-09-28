<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch\Filament;

use Aaix\LaravelIslandsSearch\Contracts\RecentHitStore;
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

    /** @var class-string<RecentHitStore>|null */
    private ?string $recentStore = null;

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

    /**
     * @param  class-string<RecentHitStore>  $store
     */
    public function recentStore(string $store): static
    {
        $this->recentStore = $store;

        return $this;
    }

    public function register(Panel $panel): void
    {
        $panel
            ->globalSearch(false)
            ->authenticatedTenantRoutes(function (): void {
                IslandsSearch::route('islands-search', $this->sources, $this->recentStore)->name('islands-search');

                if ($this->recentStore !== null) {
                    IslandsSearch::recentRoute('islands-search/recent', $this->recentStore)->name('islands-search.recent');
                }
            })
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn (): View => view('islands-search::filament.trigger', [
                'url' => Filament::getCurrentOrDefaultPanel()->route('islands-search', $this->tenantParameters()),
                'recentUrl' => $this->recentStore === null ? null : Filament::getCurrentOrDefaultPanel()->route('islands-search.recent', $this->tenantParameters()),
                'recentKey' => implode(':', array_filter(['islands-search:recent', Filament::getId(), Filament::getTenant()?->getKey(), Auth::id()], fn ($part): bool => $part !== null)),
            ]));
    }

    public function boot(Panel $panel): void {}

    /**
     * @return array<string, mixed>
     */
    private function tenantParameters(): array
    {
        return Filament::getTenant() === null ? [] : ['tenant' => Filament::getTenant()];
    }
}
