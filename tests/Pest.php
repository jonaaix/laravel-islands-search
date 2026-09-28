<?php

declare(strict_types=1);

use Aaix\LaravelIslandsSearch\Tests\FilamentTestCase;
use Aaix\LaravelIslandsSearch\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature/Core');
pest()->extend(FilamentTestCase::class)->in('Feature/Filament');
