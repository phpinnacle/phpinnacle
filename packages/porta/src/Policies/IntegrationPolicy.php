<?php

namespace PHPinnacle\Porta\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Access\Authorizable;
use PHPinnacle\Porta\Models\Integration;

class IntegrationPolicy
{
    use HandlesAuthorization;

    public function create(Authorizable $user): bool
    {
        return $user->can('create_integration');
    }

    public function delete(Authorizable $user, Integration $record): bool
    {
        return $user->can('delete_integration');
    }

    public function deleteAny(Authorizable $user): bool
    {
        return $user->can('delete_any_integration');
    }

    public function update(Authorizable $user, Integration $record): bool
    {
        return $user->can('update_integration');
    }

    public function view(Authorizable $user, Integration $record): bool
    {
        return $user->can('view_integration');
    }

    public function viewAny(Authorizable $user): bool
    {
        return $user->can('view_any_integration');
    }

    public function viewError(Authorizable $user): bool
    {
        return $user->can('view_error_integration');
    }
}
