<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Scout\Searchable;

class User extends Authenticatable implements FilamentUser
{
    use Searchable;

    protected $guarded = [];

    protected $casts = ['is_admin' => 'boolean'];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * @return array{id: int, name: string, email: string}
     */
    public function toSearchableArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email];
    }
}
