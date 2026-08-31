<?php

namespace PHPinnacle\Razor\Http\Controllers;

use PHPinnacle\Razor\Models\Document;

class DocumentController
{
    public function __invoke(string $id)
    {
        return view('phpinnacle-razor::document.show', [
            'document' => Document::query()->findOrFail($id),
        ]);
    }
}
