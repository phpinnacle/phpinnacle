<?php

namespace PHPinnacle\Cerber\Contracts;

interface HasCustomPermissions
{
    /**
     * @return list<string>
     */
    public static function getPermissions(): array;
}
