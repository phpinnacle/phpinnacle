<?php

namespace PHPinnacle\Cerber\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Access\Authorizable;
use PHPinnacle\Cerber\Models\Provider;

class ProviderPolicy
{
    use HandlesAuthorization;

    public function create(Authorizable $user): bool
    {
        return $user->can('create_provider');
    }

    public function view(Authorizable $user, Provider $record): bool
    {
        return $user->can('view_provider');
    }

    public function viewAny(Authorizable $user): bool
    {
        return $user->can('view_any_provider');
    }

    public function update(Authorizable $user, Provider $record): bool
    {
        return $user->can('update_provider');
    }

    public function delete(Authorizable $user, Provider $record): bool
    {
        return $user->can('delete_provider');
    }

    public function deleteAny(Authorizable $user): bool
    {
        return $user->can('delete_any_provider');
    }
}
