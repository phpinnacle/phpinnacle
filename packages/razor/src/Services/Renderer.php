<?php

namespace PHPinnacle\Razor\Services;

use PHPinnacle\Razor\Contracts\Engine;
use PHPinnacle\Razor\Models\Template;
use RuntimeException;

class Renderer
{
    /**
     * @var array<string, Engine>
     */
    private array $engines = [];

    public function register(Engine $engine): self
    {
        $this->engines[$engine::class] = $engine;

        return $this;
    }

    public function render(Template $template, array $context = []): string
    {
        $engine = $this->engines[$template->engine->getClass()] ?? null;

        if ($engine === null) {
            throw new RuntimeException('Engine not found');
        }

        return $engine->render($template->content, $context);
    }
}
