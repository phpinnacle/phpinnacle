<?php

namespace PHPinnacle\Cerber\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use PHPinnacle\Cerber\Enums\UserStatus;
use PHPinnacle\Cerber\Models\Password;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\Resources\Users\Actions\EmailAction;
use PHPinnacle\Cerber\Resources\Users\Actions\PasswordAction;

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
                                            ->label(__('phpinnacle-cerber::resources.user.fields.name')),
                                        Select::make('status')
                                            ->label(__('phpinnacle-cerber::resources.user.fields.status'))
                                            ->options(UserStatus::class)
                                            ->default(UserStatus::Active)
                                            ->enum(UserStatus::class)
                                            ->disabled(fn (?User $record) => $record && $record->id === auth()->id())
                                            ->selectablePlaceholder(false),
                                    ]),
                                TextInput::make('email')
                                    ->label(__('phpinnacle-cerber::resources.user.fields.email'))
                                    ->prefixIcon('phosphor-at')
                                    ->email()
                                    ->required()
                                    ->hintActions([
                                        EmailAction::markAsVerified(),
                                        EmailAction::dropVerification(),
                                        EmailAction::resendVerification(),
                                    ]),
                                TextInput::make('password')
                                    ->label(__('phpinnacle-cerber::resources.user.fields.password'))
                                    ->prefixIcon('phosphor-key')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(fn (?string $state) => !empty($state))
                                    ->required(fn (string $operation) => $operation === 'create')
                                    ->default(Password::generate())
                                    ->hintActions([
                                        PasswordAction::reset(),
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
                                'edit' => $disabled->contains($value) && !in_array($value, $state),
                                default => false,
                            })
                            ->columns(4)
                            ->hiddenLabel(),
                    ]),
            ]);
    }
}
