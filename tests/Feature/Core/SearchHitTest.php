<?php

declare(strict_types=1);

use Aaix\LaravelIslandsSearch\SearchHit;

test('a hit hands its kind and data on for a row the application draws itself', function () {
    $hit = new SearchHit('SP1001', '/orders/1', kind: 'order', data: ['customer' => 'Acme']);

    expect($hit->toArray())->toMatchArray(['title' => 'SP1001', 'kind' => 'order', 'data' => ['customer' => 'Acme']]);
});

test('a hit without a kind is drawn by the search itself', function () {
    expect((new SearchHit('Orders', '/orders'))->toArray())->toMatchArray(['kind' => null, 'data' => []]);
});
