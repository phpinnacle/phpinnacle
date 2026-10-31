<?php

namespace PHPinnacle\Eidos\Enums;

enum MappingMode: string
{
    case Whole = 'whole';
    case Item = 'item';
    case Property = 'property';
}
