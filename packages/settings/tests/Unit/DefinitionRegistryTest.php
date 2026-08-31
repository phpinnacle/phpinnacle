<?php

use PHPinnacle\Settings\Definition;
use PHPinnacle\Settings\Services\DefinitionRegistry;

it('registers definitions in their configured order', function () {
    $registry = new DefinitionRegistry;
    $later = new Definition(stdClass::class, 'Later', 'later', sort: 20);
    $earlier = new Definition(stdClass::class, 'Earlier', 'earlier', sort: 10);

    $registry->register($later, $earlier);

    expect($registry->default())
        ->toBe('earlier')
        ->and($registry->all()->keys()->all())
        ->toBe(['earlier', 'later'])
        ->and($registry->get('later'))
        ->toBe($later)
        ->and($registry->get('missing'))
        ->toBeNull();
});

it('replaces a definition registered with the same slug', function () {
    $registry = new DefinitionRegistry;
    $original = new Definition(stdClass::class, 'Original', 'general');
    $replacement = new Definition(stdClass::class, 'Replacement', 'general');

    $registry->register($original);
    $registry->register($replacement);

    expect($registry->get('general'))->toBe($replacement);
});
