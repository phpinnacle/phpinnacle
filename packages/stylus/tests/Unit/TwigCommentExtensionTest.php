<?php

use PHPinnacle\Stylus\RichEditor\TwigTemplatePlugin;

it('does not register Twig comment support', function () {
    $extensions = new TwigTemplatePlugin()->getTipTapPhpExtensions();

    expect(array_map(static fn ($extension) => $extension::$name, $extensions))->not->toContain('twigComment');
});
