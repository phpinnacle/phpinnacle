<?php

namespace PHPinnacle\Razor\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Access\Authorizable;
use PHPinnacle\Razor\Models\Template;

class TemplatePolicy
{
    use HandlesAuthorization;

    public function create(Authorizable $user): bool
    {
        return $user->can('create_template');
    }

    public function delete(Authorizable $user, Template $record): bool
    {
        return $user->can('delete_template');
    }

    public function deleteAny(Authorizable $user): bool
    {
        return $user->can('delete_any_template');
    }

    public function update(Authorizable $user, Template $record): bool
    {
        return $user->can('update_template');
    }

    public function view(Authorizable $user, Template $record): bool
    {
        return $user->can('view_template');
    }

    public function viewAny(Authorizable $user): bool
    {
        return $user->can('view_any_template');
    }
}
