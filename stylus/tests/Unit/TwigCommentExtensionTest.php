<?php

use PHPinnacle\Stylus\RichEditor\TwigTemplatePlugin;

it('does not register Twig comment support', function (): void {
    $extensions = (new TwigTemplatePlugin)->getTipTapPhpExtensions();

    expect(array_map(static fn ($extension): string => $extension::$name, $extensions))
        ->not->toContain('twigComment');
});
