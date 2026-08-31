<?php

use PHPinnacle\Recens\Models\Recent;

it('exposes recent navigation metadata', function () {
    $recent = new Recent;
    $recent->forceFill([
        'url' => '/orders/42',
        'title' => 'Order 42',
        'icon' => 'phosphor-receipt',
    ]);

    expect($recent->getUrl())
        ->toBe('/orders/42')
        ->and($recent->getLabel())
        ->toBe('Order 42')
        ->and($recent->getIcon())
        ->toBe('phosphor-receipt');
});
