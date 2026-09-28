<?php

declare(strict_types=1);

use Aaix\LaravelIslandsSearch\Tests\Fixtures\User;
use Illuminate\Testing\TestResponse;

function search(string $query): TestResponse
{
    return test()->getJson(route('search', ['q' => $query]));
}

function member(array $attributes = []): User
{
    return User::create(['name' => 'Member', 'email' => uniqid().'@example.com', 'password' => 'secret', ...$attributes]);
}

test('guests are rejected', function () {
    search('orders')->assertUnauthorized();
});

test('an empty query answers with the tips of the visible sources instead of hits', function () {
    $this->actingAs(member());

    search('')->assertOk()->assertExactJson(['data' => [
        'query' => '',
        'groups' => [],
        'icons' => [],
        'tips' => [['example' => 'orders', 'description' => 'Finds the orders page']],
    ]]);
});

test('tips of every visible source are collected in order', function () {
    $this->actingAs(member(['is_admin' => true]));

    expect(collect(search('')->json('data.tips'))->pluck('example')->all())->toBe(['orders', 'alice@example.com']);
});

test('a query answers without tips', function () {
    $this->actingAs(member());

    expect(search('order')->json('data.tips'))->toBe([]);
});

test('a query longer than the configured maximum is rejected', function () {
    $this->actingAs(member());
    config()->set('islands-search.max_query_length', 5);

    search('orders')->assertUnprocessable();
});

test('hits are grouped by source and carry their icon definitions', function () {
    $this->actingAs(member());

    $response = search('order')->assertOk();

    expect($response->json('data.groups'))->toHaveCount(1)
        ->and($response->json('data.groups.0'))->toMatchArray(['key' => 'pages', 'label' => 'Pages'])
        ->and($response->json('data.groups.0.hits.0'))->toBe(['title' => 'Orders', 'url' => '/orders', 'subtitle' => null, 'icon' => 'o-document', 'kind' => null, 'data' => []])
        ->and($response->json('data.icons.o-document.box'))->toBe('0 0 24 24');
});

test('each source contributes at most the configured number of hits', function () {
    $this->actingAs(member());
    config()->set('islands-search.limit_per_source', 2);

    expect(search('o')->json('data.groups.0.hits'))->toHaveCount(2);
});

test('sources hidden from the user are never asked', function () {
    member(['name' => 'Alice Orders']);
    $this->actingAs(member());

    expect(collect(search('alice')->json('data.groups'))->pluck('key')->all())->toBe([]);
});

test('scout sources turn matching records into hits', function () {
    $alice = member(['name' => 'Alice Example', 'email' => 'alice@example.com']);
    $this->actingAs(member(['is_admin' => true]));

    $users = collect(search('alice')->json('data.groups'))->firstWhere('key', 'users');

    expect($users['hits'])->toBe([[
        'title' => 'Alice Example',
        'url' => '/users/'.$alice->id,
        'subtitle' => 'alice@example.com',
        'icon' => 'o-user',
        'kind' => null,
        'data' => [],
    ]]);
});

test('a source that leads for the query comes first, the others keep their order', function () {
    member(['name' => 'Ore Admin', 'email' => 'ore@example.com']);
    $this->actingAs(member(['is_admin' => true]));

    expect(collect(search('ore')->json('data.groups'))->pluck('key')->all())->toBe(['users', 'pages'])
        ->and(collect(search('or')->json('data.groups'))->pluck('key')->all())->toBe(['pages', 'users']);
});
