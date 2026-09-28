<?php

declare(strict_types=1);

use Aaix\LaravelIslandsSearch\Tests\Fixtures\User;
use Filament\Facades\Filament;

test('the search box is mounted into the panel topbar with its props', function () {
    $user = User::create(['name' => 'Member', 'email' => 'member@example.com', 'password' => 'secret']);
    $this->actingAs($user);

    $response = $this->get(Filament::getPanel('admin')->getUrl())->assertOk();

    preg_match('/data-island="GlobalSearch"[^>]*data-island-payload="([^"]+)"/', $response->getContent(), $match);
    $props = json_decode(html_entity_decode($match[1] ?? ''), true)['props'] ?? [];

    expect($props['searchUrl'])->toBe(route('filament.admin.islands-search'))
        ->and($props['recentKey'])->toBe('islands-search:recent:admin:'.$user->id)
        ->and($props['recentUrl'])->toBeNull()
        ->and($props['icons'])->toHaveKey('m-magnifying-glass');
});

test('the panel endpoint searches the sources registered on the plugin', function () {
    $this->actingAs(User::create(['name' => 'Member', 'email' => 'member@example.com', 'password' => 'secret']));

    $this->getJson(route('filament.admin.islands-search', ['q' => 'order']))
        ->assertOk()
        ->assertJsonPath('data.groups.0.key', 'pages');
});

test('guests are rejected by the panel endpoint', function () {
    $this->getJson(route('filament.admin.islands-search', ['q' => 'order']))->assertUnauthorized();
});

test('the built-in filament global search is switched off', function () {
    expect(Filament::getPanel('admin')->getGlobalSearchProvider())->toBeNull();
});
