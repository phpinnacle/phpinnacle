<?php

namespace PHPinnacle\Razor\Contracts;

interface Engine
{
    public function render(string $template, array $context = []): string;
}
