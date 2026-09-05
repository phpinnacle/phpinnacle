<?php

namespace PHPinnacle\Razor\Engines;

use PHPinnacle\Razor\Contracts\Engine;
use Twig\Environment;

class Twig implements Engine
{
    public function __construct(
        private Environment $environment,
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context = []): string
    {
        return $this->environment->createTemplate($template)->render($context);
    }
}
