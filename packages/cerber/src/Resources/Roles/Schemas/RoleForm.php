<?php

namespace PHPinnacle\Cerber\Resources\Roles\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use PHPinnacle\Cerber\Forms\PermissionTabs;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Common\Forms\ActiveSelect;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('phpinnacle-cerber::resources.role.sections.general'))
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('phpinnacle-cerber::resources.role.fields.name'))
                            ->required()
                            ->columnSpan(2),
                        ActiveSelect::make()
                            ->disabled(fn (?Role $record) => $record?->is_system),
                        RichEditor::make('description')
                            ->label(__('phpinnacle-cerber::resources.role.fields.description'))
                            ->columnSpanFull(),
                    ]),
                PermissionTabs::make()
                    ->visibleOn('edit'),
            ]);
    }
}
