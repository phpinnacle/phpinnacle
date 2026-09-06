<?php

namespace PHPinnacle\Franz\Enum;

enum InitialState: string
{
    case Running = 'RUNNING';
    case Paused = 'PAUSED';
    case Stopped = 'STOPPED';
}
