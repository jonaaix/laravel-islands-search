<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch\Contracts;

use Aaix\LaravelIslandsSearch\SearchTip;

interface ProvidesSearchTips
{
    /**
     * @return list<SearchTip>
     */
    public function tips(): array;
}
