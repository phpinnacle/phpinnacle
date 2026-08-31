<?php

use PHPinnacle\Tags\Models\Tag;
use PHPinnacle\Tags\TagsPlugin;

it('exposes tag presentation metadata', function () {
    $tag = new Tag;
    $tag->forceFill([
        'name' => 'Featured',
        'color' => '#2563eb',
    ]);

    expect($tag->getLabel())->toBe('Featured')->and($tag->getColor())->toHaveKeys([50, 500, 950]);
});

it('exposes a stable Filament plugin identifier', function () {
    expect(new TagsPlugin()->getId())->toBe('phpinnacle/tags');
});
