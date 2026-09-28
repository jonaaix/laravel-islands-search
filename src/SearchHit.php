<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch;

final readonly class SearchHit
{
    public function __construct(
        public string $title,
        public string $url,
        public ?string $subtitle = null,
        public ?string $icon = null,
    ) {}

    /**
     * @return array{title: string, url: string, subtitle: ?string, icon: ?string}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'url' => $this->url,
            'subtitle' => $this->subtitle,
            'icon' => $this->icon,
        ];
    }
}
