<?php

namespace PHPinnacle\Cerber\Forms;

use Filament\Forms\Components\Select;
use PHPinnacle\Cerber\Models\Role;

class RoleSelect extends Select
{
    public static function getDefaultName(): string
    {
        return 'role_id';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-cerber::forms.role.label'))
            ->placeholder(__('phpinnacle-cerber::forms.role.placeholder'))
            ->options(fn () => Role::list());
    }
}
