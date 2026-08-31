<?php

namespace PHPinnacle\Stylus\Support;

final class HtmlFormatter
{
    public static function format(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $html = preg_replace('/(?<=>)(?=<)/', "\n", $html) ?? $html;

        $indent = 0;
        $lines = array_filter(array_map('trim', explode("\n", $html)));
        $result = [];
        $void = '/^<(br|hr|img|input|link|meta)(\s[^>]*)?\/?>$/i';
        $close = '/^<\//i';
        $open = '/^<([a-zA-Z][^>\/]*)>$/i';

        foreach ($lines as $line) {
            if (preg_match($close, $line)) {
                $indent = max(0, $indent - 2);
            }

            $result[] = str_repeat(' ', $indent) . $line;

            if (preg_match($open, $line) && !preg_match($void, $line)) {
                $indent += 2;
            }
        }

        return implode("\n", $result);
    }
}
