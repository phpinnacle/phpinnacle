<?php

namespace PHPinnacle\Langcat\Enum;

enum StudentType: string
{
    case Regular = 'regular';
    case Registered = 'registered';
    case Lead = 'lead';
}
