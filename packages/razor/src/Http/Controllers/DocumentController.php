<?php

namespace PHPinnacle\Razor\Http\Controllers;

use Illuminate\Contracts\View\View;
use PHPinnacle\Razor\Models\Document;

class DocumentController
{
    public function __invoke(string $id): View
    {
        return view('phpinnacle-razor::document.show', [
            'document' => Document::query()->findOrFail($id),
        ]);
    }
}
