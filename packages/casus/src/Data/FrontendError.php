<?php

namespace PHPinnacle\Casus\Data;

final readonly class FrontendError
{
    public function __construct(
        public string $type,
        public string $message,
        public string $file = '',
        public int $line = 0,
        public int $column = 0,
        public string $trace = '',
        public ?string $path = null,
    ) {}

    public function fingerprint(): string
    {
        return hash('sha256', implode("\0", [
            'browser',
            $this->type,
            $this->message,
            $this->file,
            $this->line,
            $this->column,
        ]));
    }

    /**
     * @return array{
     *     sapi: string,
     *     type: string,
     *     code: string,
     *     file: string,
     *     line: int,
     *     message: string,
     *     trace: string,
     *     context: array{column: int},
     *     path: string|null,
     * }
     */
    public function attributes(?string $requestPath): array
    {
        return [
            'sapi' => 'browser',
            'type' => $this->type,
            'code' => '',
            'file' => $this->file,
            'line' => $this->line,
            'message' => $this->message,
            'trace' => $this->trace,
            'context' => ['column' => $this->column],
            'path' => $this->path ?? $requestPath,
        ];
    }
}
