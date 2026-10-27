<?php

namespace PHPinnacle\Common\Enums;

enum TabPosition: string
{
    case Top = 'top';
    case Bottom = 'bottom';
    case Start = 'start';
    case End = 'end';

    public function isVertical(): bool
    {
        return $this === self::Start || $this === self::End;
    }
}
