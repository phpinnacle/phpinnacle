<?php

namespace PHPinnacle\Cerber\Resources\Roles\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Resources\Roles\RoleResource;

/**
 * @property Role $record
 */
class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    public array $permissions = [];

    public function getTitle(): string|Htmlable
    {
        return __('phpinnacle-cerber::resources.role.pages.create');
    }

    protected function afterSave(): void
    {
        $this->record->grant(...$this->permissions);

        $this->permissions = [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->permissions = Arr::flatten($data['permissions'] ?? []);

        return Arr::except($data, 'permissions');
    }
}
