<?php

declare(strict_types=1);

use Aaix\LaravelIslandsSearch\Support\HeroiconSet;
use Aaix\LaravelIslandsSearch\Tests\Fixtures\User;
use Illuminate\Support\Facades\Blade;

/**
 * @return array<string, mixed>
 */
function triggerProps(string $html): array
{
    preg_match('/data-island="GlobalSearch"[^>]*data-island-payload="([^"]+)"/', $html, $match);

    return json_decode(html_entity_decode($match[1] ?? ''), true)['props'] ?? [];
}

test('the trigger mounts the search island with the given url and a per-user recent key', function () {
    $user = User::create(['name' => 'Member', 'email' => 'member@example.com', 'password' => 'secret']);
    $this->actingAs($user);

    $props = triggerProps(Blade::render('<x-islands-search::search-trigger url="/search" />'));

    expect($props['searchUrl'])->toBe('/search')
        ->and($props['recentKey'])->toBe('islands-search:recent:'.$user->id)
        ->and($props['recentLimit'])->toBe(8)
        ->and($props['icons'])->toHaveKey('m-magnifying-glass');
});

test('the host may name its own recent key and place the trigger with classes', function () {
    $html = Blade::render('<x-islands-search::search-trigger url="/search" recent-key="shop:recent" class="ms-auto" />');

    expect(triggerProps($html)['recentKey'])->toBe('shop:recent')
        ->and($html)->toContain('ms-auto');
});

test('the icon set resolves heroicons and drops unknown names', function () {
    $icons = app(HeroiconSet::class)->build(['o-user', 'm-magnifying-glass', 'o-does-not-exist', 'o-user']);

    expect(array_keys($icons))->toBe(['o-user', 'm-magnifying-glass'])
        ->and($icons['o-user'])->toMatchArray(['box' => '0 0 24 24', 'stroke' => true])
        ->and($icons['m-magnifying-glass'])->toMatchArray(['box' => '0 0 20 20', 'stroke' => false])
        ->and($icons['o-user']['html'])->toStartWith('<path');
});
