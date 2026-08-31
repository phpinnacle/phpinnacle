<?php

use PHPinnacle\Stylus\Twig\TwigTemplateValidator;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

it('accepts a template supported by the provided Twig environment', function () {
    $validator = new TwigTemplateValidator(new Environment(new ArrayLoader));

    $result = $validator->validate(implode("\n", [
        '{% if user.active %}',
        '  {% for product in products %}',
        "    <p>{{ product.name|default('Untitled')|title }}</p>",
        '  {% endfor %}',
        '{% else %}',
        '  <p>Account disabled</p>',
        '{% endif %}',
    ]));

    expect($result->isValid())->toBeTrue()->and($result->errors)->toBe([]);
});

it('returns the Twig syntax error and source position', function () {
    $validator = new TwigTemplateValidator(new Environment(new ArrayLoader));

    $result = $validator->validate("{% if user.active %}\n<p>Missing endif</p>");

    expect($result->isValid())
        ->toBeFalse()
        ->and($result->errors)
        ->toHaveCount(1)
        ->and($result->errors[0]['message'])
        ->toContain('Unexpected end of template')
        ->and($result->errors[0]['line'])
        ->toBe(2);
});

it('uses the provided environment filter registry', function () {
    $validator = new TwigTemplateValidator(new Environment(new ArrayLoader));

    $result = $validator->validate('{{ user.name|application_filter }}');

    expect($result->isValid())
        ->toBeFalse()
        ->and($result->errors[0]['message'])
        ->toContain('Unknown "application_filter" filter');
});
