<?php

namespace PHPinnacle\Rosetta\Enums;

enum FieldType: string
{
    case Boolean = 'boolean';
    case Number = 'number';
    case Date = 'date';
    case Time = 'time';
    case DateTime = 'datetime';
    case String = 'string';
    case Binary = 'binary';
    case Id = 'id';
    case Uuid = 'uuid';
    case Reference = 'reference';
    case Union = 'union';
    case Unknown = 'unknown';
    case Undefined = 'undefined';
}
