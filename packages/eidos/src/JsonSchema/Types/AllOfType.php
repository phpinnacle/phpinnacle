<?php

namespace PHPinnacle\Eidos\JsonSchema\Types;

class AllOfType extends CompositionType
{
    public function keyword(): string
    {
        return 'allOf';
    }
}
