<?php

namespace PHPinnacle\Porta\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPinnacle\Porta\Enums\IntegrationResponse;
use PHPinnacle\Porta\Models\Integration;

class CatchController
{
    private const array DEFAULT = [
        'test' => 'test',
    ];

    public function __invoke(string $id, Request $request): Response
    {
        $integration = Integration::get($id);
        $integration->authorize($request);

        if ($request->json('test') === 'test') {
            return response(self::DEFAULT);
        }

        $webhook = $integration->schedule(
            $request->host(),
            (string) $request->getContent(),
            $request->headers->all(),
        );
        $webhook->save();

        return match ($integration->response_kind) {
            IntegrationResponse::Empty => response(null, $integration->response_code),
            IntegrationResponse::Model => response($webhook->render(), $integration->response_code),
            IntegrationResponse::Identity => response(['id' => $webhook->id], $integration->response_code),
        };
    }
}
