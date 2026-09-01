<?php

namespace PHPinnacle\Canvio\Support;

/** @internal */
final readonly class LinkHeader
{
    public static function next(string $header): ?string
    {
        preg_match_all('/<([^>]+)>\s*;\s*rel="([^"]+)"/i', $header, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (in_array('next', preg_split('/\s+/', strtolower($match[2])) ?: [], true)) {
                return $match[1];
            }
        }

        return null;
    }
}
