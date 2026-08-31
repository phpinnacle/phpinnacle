<?php

namespace PHPinnacle\Cerber\Console;

use Illuminate\Console\Command;
use PHPinnacle\Cerber\Cerberus;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Models\Tenant;

class SyncPermissions extends Command
{
    public $signature = 'auth:sync-permissions';

    public function handle(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            Role::query()
                ->where('tenant_id', $tenant->getKey())
                ->where('is_system', true)
                ->each(function (Role $role) {
                    $role->grant(...Cerberus::getPermissions(flatten: true));
                });
        }
    }
}
