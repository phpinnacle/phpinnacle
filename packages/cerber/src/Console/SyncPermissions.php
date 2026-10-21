<?php

namespace PHPinnacle\Cerber\Console;

use Illuminate\Console\Command;
use PHPinnacle\Cerber\Cerberus;
use PHPinnacle\Cerber\Models\Role;

class SyncPermissions extends Command
{
    public $signature = 'auth:sync-permissions';

    public function handle(): void
    {
        $permissions = [...Cerberus::getPermissions(flatten: true), ...Cerberus::getGuards()];

        Role::query()
            ->where('is_system', true)
            ->each(fn (Role $role) => $role->grant(...$permissions));
    }
}
