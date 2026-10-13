<?php

namespace PHPinnacle\Eidos\JsonSchema\Types;

class OneOfType extends CompositionType
{
    public function keyword(): string
    {
        return 'oneOf';
    }
}
