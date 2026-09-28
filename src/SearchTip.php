<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch;

final readonly class SearchTip
{
    public function __construct(
        public string $example,
        public string $description,
    ) {}

    /**
     * @return array{example: string, description: string}
     */
    public function toArray(): array
    {
        return [
            'example' => $this->example,
            'description' => $this->description,
        ];
    }
}
