<?php

namespace PHPinnacle\Cerber\Resources\Roles\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Resources\Roles\RoleResource;

/**
 * @property Role $record
 */
class EditRole extends EditRecord
{
    public static bool $formActionsAreSticky = true;

    protected static string $resource = RoleResource::class;

    public array $permissions = [];

    public function getTitle(): string|Htmlable
    {
        return __('phpinnacle-cerber::resources.role.pages.edit');
    }

    protected function afterSave(): void
    {
        $this->record->grant(...$this->permissions);

        $this->permissions = [];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label(__('phpinnacle-cerber::resources.role.actions.delete'))
                ->visible(fn (Role $record) => !$record->is_system),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->permissions = Arr::flatten($data['permissions'] ?? []);

        return Arr::except($data, 'permissions');
    }
}
