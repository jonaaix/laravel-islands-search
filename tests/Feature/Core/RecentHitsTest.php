<?php

declare(strict_types=1);

use Aaix\LaravelIslandsSearch\Tests\Fixtures\ArrayRecentStore;
use Aaix\LaravelIslandsSearch\Tests\Fixtures\User;

beforeEach(function () {
    ArrayRecentStore::$hits = [];
    $this->actingAs(User::create(['name' => 'Member', 'email' => uniqid().'@example.com', 'password' => 'secret']));
});

function rememberHit(array $hit): Illuminate\Testing\TestResponse
{
    return test()->postJson(route('remembering-search.recent'), $hit);
}

test('an opened hit is kept in the store and comes back with the empty query, newest first', function () {
    rememberHit(['title' => 'Orders', 'url' => '/orders', 'icon' => 'o-document'])->assertNoContent();
    rememberHit(['title' => 'SP1001', 'url' => '/orders/1', 'kind' => 'order', 'data' => ['customer' => 'Acme']])->assertNoContent();

    $data = $this->getJson(route('remembering-search', ['q' => '']))->assertOk()->json('data');

    expect(array_column($data['recent'], 'title'))->toBe(['SP1001', 'Orders'])
        ->and($data['recent'][0]['data'])->toBe(['customer' => 'Acme'])
        ->and($data['icons'])->toHaveKey('o-document');
});

test('opening a hit again moves it to the front instead of listing it twice', function () {
    rememberHit(['title' => 'Orders', 'url' => '/orders']);
    rememberHit(['title' => 'Invoices', 'url' => '/invoices']);
    rememberHit(['title' => 'Orders', 'url' => '/orders']);

    expect(array_column($this->getJson(route('remembering-search'))->json('data.recent'), 'title'))->toBe(['Orders', 'Invoices']);
});

test('the store keeps no more than the configured number of hits', function () {
    config()->set('islands-search.recent_limit', 2);

    foreach (['a', 'b', 'c'] as $page) {
        rememberHit(['title' => $page, 'url' => '/'.$page]);
    }

    expect(array_column($this->getJson(route('remembering-search'))->json('data.recent'), 'title'))->toBe(['c', 'b']);
});

test('a link leading off the site is refused', function () {
    rememberHit(['title' => 'Elsewhere', 'url' => 'https://evil.example/login'])->assertUnprocessable();
    rememberHit(['title' => 'Elsewhere', 'url' => '//evil.example/login'])->assertUnprocessable();

    expect(ArrayRecentStore::$hits)->toBe([]);
});

test('a query answers without recent hits', function () {
    rememberHit(['title' => 'Orders', 'url' => '/orders']);

    expect($this->getJson(route('remembering-search', ['q' => 'ord']))->json('data.recent'))->toBe([]);
});
