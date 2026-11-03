<?php

namespace PHPinnacle\Cerber\OpenApi;

use OpenApi\Spec as OA;

#[OA\Response(
    component: 'CerberUnauthorized',
    description: 'Unauthenticated.',
    content: new OA\Schema(properties: [
        'message' => new OA\Schema(
            type: 'string',
        ),
    ], type: 'object'),
)]
#[OA\Response(
    component: 'CerberForbidden',
    description: 'Access denied or invalid verification signature.',
    content: new OA\Schema(properties: [
        'message' => new OA\Schema(
            type: 'string',
        ),
    ], type: 'object'),
)]
#[OA\Response(
    component: 'CerberNotFound',
    description: 'Login not found.',
    content: new OA\Schema(properties: [
        'message' => new OA\Schema(
            type: 'string',
        ),
    ], type: 'object'),
)]
#[OA\Response(
    component: 'CerberUnprocessableContent',
    description: 'Validation failed.',
    content: new OA\Schema(
        properties: [
            'message' => new OA\Schema(type: 'string'),
            'errors' => new OA\Schema(
                type: 'object',
                additionalProperties: new OA\Schema(
                    type: 'array',
                    items: new OA\Schema(type: 'string'),
                ),
            ),
        ],
        type: 'object',
    ),
)]
#[OA\Security\Scheme\Http(
    component: 'cerberBearerAuth',
    bearerFormat: 'JWT',
    scheme: 'bearer',
)]
class Components {}
