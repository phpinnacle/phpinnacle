<?php

namespace CerberOpenApiTests;

use OpenApi\Builder;
use OpenApi\Loggers\CollectingLogger;
use OpenApi\Spec as OA;

#[OA\Info(version: '1.0.0', title: 'Cerber API')]
#[OA\Server(url: '/api')]
#[OA\Components(securitySchemes: [new OA\Security\Scheme\Http(
    component: 'applicationBearerAuth',
    scheme: 'bearer',
)])]
#[OA\OpenApi(security: [new OA\Security\Requirement(scheme: 'applicationBearerAuth')])]
class OpenApiDocument {}

it('generates the authentication contract without overriding application components', function () {
    $logger = new CollectingLogger;
    $document = new Builder()
        ->setLogger($logger)
        ->addSource([__DIR__ . '/../../src', __FILE__])
        ->build();
    $specification = $document->toArray();

    expect(array_keys($specification['paths']))->toEqualCanonicalizing([
        '/auth/register',
        '/auth/login',
        '/auth/verify-email',
        '/auth/password/forgot',
        '/auth/password/reset',
        '/auth/password/change',
        '/auth/refresh',
        '/auth/logout',
        '/profile',
    ]);
    expect(array_values(array_filter($logger->entries(), fn (array $entry) => in_array(
        $entry['level'],
        [
            'error',
            'warning',
        ],
        strict: true,
    ))))->toBe([]);
    expect($document->isValid())->toBeTrue();
    expect($specification['components']['securitySchemes'])->toHaveKeys(['applicationBearerAuth', 'cerberBearerAuth']);
    expect(
        $specification['components']['schemas']['CerberAuthResource']['properties']['prerequisites']['items']['enum'],
    )
        ->toBe(['password']);
    expect($specification['paths']['/auth/password/change']['post']['responses'])->toHaveKey('204');
    $loginSchema =
        $specification['paths']['/auth/login']['post']['requestBody']['content']['application/json']['schema'];
    expect($loginSchema['required'])->toBe(['username', 'password']);
    expect($loginSchema['properties'])->not->toHaveKey('email');
    expect($loginSchema['properties']['username']['maxLength'])->toBe(255);
    expect(array_column($specification['paths']['/auth/verify-email']['get']['parameters'], 'name'))
        ->toBe(['id', 'hash', 'expires', 'signature']);

    foreach (['/auth/register', '/auth/password/reset', '/auth/password/change'] as $path) {
        $schema = $specification['paths'][$path]['post']['requestBody']['content']['application/json']['schema'];
        expect($schema['properties'])->not->toHaveKey('password_confirmation');
        expect($schema['properties']['password']['minLength'])->toBe(8);
    }
});
