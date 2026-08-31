<?php

namespace PHPinnacle\Cerber\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Access\Authorizable;
use PHPinnacle\Cerber\Models\Role;

class RolePolicy
{
    use HandlesAuthorization;

    public function create(Authorizable $user): bool
    {
        return $user->can('create_role');
    }

    public function delete(Authorizable $user, Role $record): bool
    {
        return !$record->is_system && $user->can('delete_role');
    }

    public function deleteAny(Authorizable $user): bool
    {
        return $user->can('delete_any_role');
    }

    public function update(Authorizable $user, Role $record): bool
    {
        return $user->can('update_role');
    }

    public function view(Authorizable $user, Role $record): bool
    {
        return $user->can('view_role');
    }

    public function viewAny(Authorizable $user): bool
    {
        return $user->can('view_any_role');
    }
}
