<?php

namespace PHPinnacle\Stylus\Twig;

final readonly class TemplateValidationResult
{
    /**
     * @param  list<array{message: string, line: int, column: int|null}>  $errors
     */
    private function __construct(
        public array $errors,
    ) {}

    public static function valid(): self
    {
        return new self([]);
    }

    public static function invalid(string $message, int $line, ?int $column): self
    {
        return new self([[
            'message' => $message,
            'line' => $line,
            'column' => $column,
        ]]);
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }
}
