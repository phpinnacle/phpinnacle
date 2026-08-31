<?php

namespace PHPinnacle\Cerber\Contracts;

interface HasCustomPermissions
{
    public static function getPermissions(): array;
}
