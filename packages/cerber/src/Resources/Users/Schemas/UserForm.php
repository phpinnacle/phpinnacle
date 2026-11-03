<?php

namespace PHPinnacle\Cerber\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use PHPinnacle\Cerber\Enums\UserStatus;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\Schemas\Logins;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $disabled = Role::query()->where('is_active', false)->pluck('id');

        return $schema
            ->columns(1)
            ->components([
                Section::make(__('phpinnacle-cerber::resources.user.sections.general'))
                    ->columns(4)
                    ->schema([
                        Group::make()
                            ->columnSpan(3)
                            ->schema([
                                Group::make()
                                    ->columns()
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('phpinnacle-cerber::resources.user.fields.name'))
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('password')
                                            ->label(__('phpinnacle-cerber::pages.profile.fields.password'))
                                            ->password()
                                            ->revealable()
                                            ->minLength(8)
                                            ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                                            ->dehydrated(fn (?string $state) => filled($state)),
                                        Toggle::make('is_password_temporary')
                                            ->label(__(
                                                'phpinnacle-cerber::resources.user.fields.is_password_temporary',
                                            ))
                                            ->default(false),
                                        Select::make('status')
                                            ->label(__('phpinnacle-cerber::resources.user.fields.status'))
                                            ->options(UserStatus::class)
                                            ->default(UserStatus::Active)
                                            ->enum(UserStatus::class)
                                            ->disabled(
                                                fn (?User $record) => $record && $record->id === User::current()->id,
                                            )
                                            ->selectablePlaceholder(false),
                                    ]),
                            ]),
                        FileUpload::make('avatar')
                            ->hiddenLabel()
                            ->alignEnd()
                            ->disk('public')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->circleCropper(),
                    ]),
                Logins::make()->administrative(),
                Section::make(__('phpinnacle-cerber::resources.user.sections.roles'))
                    ->schema([
                        CheckboxList::make('roles')
                            ->relationship('roles', 'name')
                            ->descriptions(
                                fn () => Role::query()
                                    ->pluck('description', 'id')
                                    ->map(fn (string $description) => Str::limit(strip_tags($description), 40))
                                    ->all(),
                            )
                            ->disableOptionWhen(fn (string $operation, string $value, array $state) => match (
                                $operation
                            ) {
                                'create' => $disabled->contains($value),
                                'edit' => $disabled->contains($value) && !in_array($value, $state, strict: true),
                                default => false,
                            })
                            ->columns(4)
                            ->hiddenLabel(),
                    ]),
            ]);
    }
}
