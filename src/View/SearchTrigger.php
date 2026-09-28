<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch\View;

use Aaix\LaravelIslandsSearch\Support\HeroiconSet;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class SearchTrigger extends Component
{
    public function __construct(
        public string $url,
        public ?string $recentKey = null,
    ) {}

    /**
     * @return array{searchUrl: string, recentKey: string, recentLimit: int, icons: array<string, array{box: string, stroke: bool, html: string}>}
     */
    public function islandProps(): array
    {
        return [
            'searchUrl' => $this->url,
            'recentKey' => $this->recentKey ?? implode(':', array_filter(['islands-search:recent', Auth::id()], fn ($part): bool => $part !== null)),
            'recentLimit' => (int) config('islands-search.recent_limit'),
            'icons' => app(HeroiconSet::class)->build(['m-magnifying-glass', 'o-information-circle']),
        ];
    }

    public function render(): View
    {
        return view('islands-search::trigger');
    }
}
