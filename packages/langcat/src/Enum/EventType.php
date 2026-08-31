<?php

namespace PHPinnacle\Langcat\Enum;

enum EventType: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Undelete = 'undelete';
    case Archive = 'archive';
    case Restore = 'restore';
}
