<?php

namespace PHPinnacle\Razor\Contracts;

interface Engine
{
    /**
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context = []): string;
}
