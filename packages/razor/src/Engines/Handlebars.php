<?php

namespace PHPinnacle\Razor\Engines;

use Closure;
use LightnCandy\Flags;
use LightnCandy\LightnCandy;
use PHPinnacle\Razor\Contracts\Engine;

readonly class Handlebars implements Engine
{
    /**
     * @param array<string, callable|string> $helpers
     */
    public function __construct(
        private int $flags,
        private array $helpers,
    ) {}

    public static function instance(): self
    {
        return new self(Flags::FLAG_ELSE
        | Flags::FLAG_PROPERTY
        | Flags::FLAG_HANDLEBARSJS
        | Flags::FLAG_STANDALONEPHP, [
            'date' => function ($value, $format) {
                $format = is_string($format) ? $format : 'd.m.Y';

                return $value?->format($format);
            },
        ]);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context = []): string
    {
        $compiled = LightnCandy::compile($template, [
            'flags' => $this->flags,
            'helpers' => $this->helpers,
        ]);

        $context['now'] = now();

        /** @var Closure $render */
        $render = LightnCandy::prepare($compiled);

        return $render($context);
    }
}
