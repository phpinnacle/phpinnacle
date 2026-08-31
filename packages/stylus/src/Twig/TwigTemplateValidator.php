<?php

namespace PHPinnacle\Stylus\Twig;

use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Source;

final readonly class TwigTemplateValidator
{
    public function __construct(
        private Environment $twig,
    ) {}

    public function validate(string $template, string $name = 'template.html.twig'): TemplateValidationResult
    {
        try {
            $tokens = $this->twig->tokenize(new Source($template, $name));
            $this->twig->parse($tokens);
        } catch (SyntaxError $error) {
            return TemplateValidationResult::invalid(
                message: $error->getRawMessage(),
                line: $error->getTemplateLine(),
                column: $error->getTemplateColumn(),
            );
        }

        return TemplateValidationResult::valid();
    }
}
