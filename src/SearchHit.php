<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch;

final readonly class SearchHit
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $title,
        public string $url,
        public ?string $subtitle = null,
        public ?string $icon = null,
        public ?string $kind = null,
        public array $data = [],
    ) {}

    /**
     * @return array{title: string, url: string, subtitle: ?string, icon: ?string, kind: ?string, data: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'url' => $this->url,
            'subtitle' => $this->subtitle,
            'icon' => $this->icon,
            'kind' => $this->kind,
            'data' => $this->data,
        ];
    }
}
