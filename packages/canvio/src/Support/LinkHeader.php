<?php

namespace PHPinnacle\Canvio\Support;

/** @internal */
final readonly class LinkHeader
{
    public static function next(string $header): ?string
    {
        preg_match_all('/<([^>]+)>\s*;\s*rel="([^"]+)"/i', $header, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $relations = preg_split('/\s+/', strtolower($match[2]));

            if ($relations !== false && in_array('next', $relations, true)) {
                return $match[1];
            }
        }

        return null;
    }
}
