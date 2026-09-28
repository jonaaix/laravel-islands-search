<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch\Contracts;

interface LeadsForQuery
{
    public function leadsFor(string $query): bool;
}
