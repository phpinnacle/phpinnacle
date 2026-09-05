<?php

namespace PHPinnacle\Cerber\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'panel' => ['required', 'string'],
            'credentials' => ['required', 'string'],
        ];
    }
}
