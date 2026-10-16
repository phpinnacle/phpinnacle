<?php

namespace PHPinnacle\Eidos\Http\Controllers;

use BladeUI\Icons\Exceptions\SvgNotFound;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SchemaIconController
{
    public function __invoke(Request $request, string $icon): Response
    {
        try {
            $svg = svg($icon)->toHtml();
        } catch (SvgNotFound) {
            abort(404);
        }

        $response = response($svg, headers: [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
        $response->setEtag(hash('sha256', $svg));
        $response->isNotModified($request);

        return $response;
    }
}
