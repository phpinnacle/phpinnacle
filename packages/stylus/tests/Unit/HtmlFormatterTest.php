<?php

use PHPinnacle\Stylus\Support\HtmlFormatter;

it('formats nested HTML with stable indentation', function () {
    expect(HtmlFormatter::format('<article><p>Hello</p><br></article>'))
        ->toBe(implode("\n", [
            '<article>',
            '  <p>Hello</p>',
            '  <br>',
            '</article>',
        ]));
});

it('normalizes blank HTML to an empty string', function (?string $html) {
    expect(HtmlFormatter::format($html))->toBe('');
})->with([null, '', '   ']);
