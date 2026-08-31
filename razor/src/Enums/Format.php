<?php

namespace PHPinnacle\Razor\Enums;

enum Format: string
{
    case HTML = 'html';
    case BLOCK = 'block';

    public function decode(string $value): mixed
    {
        return match ($this) {
            self::HTML => $value,
            self::BLOCK => json_decode($value, flags: JSON_THROW_ON_ERROR),
        };
    }

    public function encode(mixed $value): string
    {
        return match ($this) {
            self::HTML => $value,
            self::BLOCK => json_encode($value, flags: JSON_THROW_ON_ERROR),
        };
    }
}
