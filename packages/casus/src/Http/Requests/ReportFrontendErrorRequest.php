<?php

namespace PHPinnacle\Casus\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use PHPinnacle\Casus\Data\FrontendError;

class ReportFrontendErrorRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:16000'],
            'file' => ['nullable', 'string', 'max:255'],
            'line' => ['nullable', 'integer', 'between:0,2147483647'],
            'column' => ['nullable', 'integer', 'between:0,2147483647'],
            'trace' => ['nullable', 'string', 'max:16000'],
            'path' => ['nullable', 'string', 'max:255', 'regex:/\A\/[^?#]*\z/u'],
        ];
    }

    public function error(): FrontendError
    {
        /** @var array{type: string, message: string, file?: string|null, line?: int|numeric-string|null, column?: int|numeric-string|null, trace?: string|null, path?: string|null} $data */
        $data = $this->validated();

        return new FrontendError(
            type: $data['type'],
            message: $data['message'],
            file: $data['file'] ?? '',
            line: (int) ($data['line'] ?? 0),
            column: (int) ($data['column'] ?? 0),
            trace: $data['trace'] ?? '',
            path: $data['path'] ?? null,
        );
    }
}
