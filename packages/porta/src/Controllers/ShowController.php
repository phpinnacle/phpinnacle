<?php

namespace PHPinnacle\Porta\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPinnacle\Porta\Models\Integration;

class ShowController
{
    public function __invoke(string $id, Request $request): Response
    {
        $integration = Integration::query()->findOrFail($id);

        return response([
            'id' => $integration->id,
            'type' => $integration->type,
        ]);
    }
}
