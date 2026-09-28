<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch\Tests\Fixtures;

use Aaix\LaravelIslandsSearch\Contracts\ProvidesSearchTips;
use Aaix\LaravelIslandsSearch\SearchHit;
use Aaix\LaravelIslandsSearch\SearchTip;
use Aaix\LaravelIslandsSearch\Sources\ScoutSource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends ScoutSource<User>
 */
class AdminUsersSource extends ScoutSource implements ProvidesSearchTips
{
    public function key(): string
    {
        return 'users';
    }

    public function label(): string
    {
        return 'Users';
    }

    public function isVisibleTo(Authenticatable $user): bool
    {
        return $user instanceof User && $user->is_admin;
    }

    public function tips(): array
    {
        return [new SearchTip('alice@example.com', 'Finds a user by email')];
    }

    protected function model(): string
    {
        return User::class;
    }

    protected function toHit(Model $record): SearchHit
    {
        return new SearchHit($record->name, '/users/'.$record->id, $record->email, 'o-user');
    }
}
