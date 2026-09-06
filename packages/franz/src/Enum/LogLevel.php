<?php

namespace PHPinnacle\Franz\Enum;

enum LogLevel: string
{
    case Off = 'OFF';
    case Fatal = 'FATAL';
    case Error = 'ERROR';
    case Warn = 'WARN';
    case Info = 'INFO';
    case Debug = 'DEBUG';
    case Trace = 'TRACE';
    case All = 'ALL';
}
