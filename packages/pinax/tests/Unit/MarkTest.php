<?php

use PHPinnacle\Pinax\Mark;

it('toggles a unique mark between gallery items', function () {
    $mark = Mark::make('featured')->unique();
    $state = [
        'first' => ['marks' => []],
        'second' => ['marks' => ['featured']],
    ];

    $updated = $mark->toggle('first', $state);

    expect($updated['first']['marks'])
        ->toContain('featured')
        ->and($updated['second']['marks'])
        ->not
        ->toContain('featured')
        ->and($mark->exists($updated))
        ->toBeTrue()
        ->and($mark->getIcon($updated['first']))
        ->toBe('phosphor-star-fill')
        ->and($mark->getColor($updated['first']))
        ->toBe('primary');
});
