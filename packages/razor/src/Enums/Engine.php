<?php

namespace PHPinnacle\Razor\Enums;

use PHPinnacle\Razor\Engines\Handlebars;
use PHPinnacle\Razor\Engines\Twig;

enum Engine: string
{
    case Handlebars = 'handlebars';
    case Twig = 'twig';

    public function getClass(): string
    {
        return match ($this) {
            self::Handlebars => Handlebars::class,
            self::Twig => Twig::class,
        };
    }
}
