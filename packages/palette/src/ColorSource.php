<?php

namespace PHPinnacle\Palette;

enum ColorSource: string
{
    case Tailwind = 'tailwind';
    case Filament = 'filament';
    case Custom = 'custom';
}
