<?php

namespace PHPinnacle\Cerber\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Access\Authorizable;
use PHPinnacle\Cerber\Models\User;

class UserPolicy
{
    use HandlesAuthorization;

    public function create(Authorizable $user): bool
    {
        return $user->can('create_user');
    }

    public function delete(Authorizable $user, User $record): bool
    {
        return $user->can('delete_user');
    }

    public function deleteAny(Authorizable $user): bool
    {
        return $user->can('delete_any_user');
    }

    public function update(Authorizable $user, User $record): bool
    {
        return $user->can('update_user');
    }

    public function view(Authorizable $user, User $record): bool
    {
        return $user->can('view_user');
    }

    public function viewAny(Authorizable $user): bool
    {
        return $user->can('view_any_user');
    }
}
