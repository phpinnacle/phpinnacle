<?php

namespace PHPinnacle\Franz\Enum;

enum LoggerScope: string
{
    case Worker = 'worker';
    case Cluster = 'cluster';
}
