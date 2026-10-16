<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Js;
use Tests\TestCase;

uses(TestCase::class);

it('serves registered schema icons with browser caching and conditional requests', function () {
    $url = route('phpinnacle-eidos.icons', ['icon' => 'heroicon-o-envelope']);
    $response = $this
        ->get($url)
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertSee('<svg', escape: false);

    expect($response->headers->get('Cache-Control'))->toContain('public', 'max-age=86400');
    expect($response->headers->get('ETag'))->not->toBeNull();
    expect(new DOMDocument()->loadXML($response->getContent()))->toBeTrue();
    $this->get($url, ['If-None-Match' => $response->headers->get('ETag')])->assertStatus(304);
});

it('returns not found for unknown icons and paths outside registered icon names', function (string $icon) {
    $this->get('/phpinnacle/eidos/icons/' . $icon . '.svg')->assertNotFound();
})->with(['heroicon-o-nonexistent', 'heroicon-o-user.svg', '..%2F..%2F.env', '%3Csvg%3E']);

it('renders standalone Blade schema components with automatic icons and without a caller supplied SVG catalog', function (
    string $component,
) {
    $html = Blade::render(
        '<x-phpinnacle-eidos::'
        . $component
        . ' schema="document" value="selection" choose="selection = source.value" label="\'Schema\'" />',
    );

    expect($html)->toContain(
        'eidos-json-schema-tree__icon-mask',
        Js::from(route('phpinnacle-eidos.icons', [
            'icon' => '__icon__',
        ]))->toHtml(),
    );
    expect($html)->toContain('!source.iconHtml && source.icon');
})->with(['json-schema-picker', 'json-schema-entry']);
