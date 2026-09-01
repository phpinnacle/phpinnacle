<?php

namespace PHPinnacle\Canvio\Enum;

enum LatePolicyStatus: string
{
    case Extended = 'extended';
    case Late = 'late';
    case Missing = 'missing';
    case None = 'none';
}
