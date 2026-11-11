<?php

namespace PHPinnacle\Cerber\Resources\Users\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Flex;
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
                Section::make()
                    ->divided()
                    ->schema([
                        Flex::make([
                            FileUpload::make('avatar')
                                ->hiddenLabel()
                                ->alignCenter()
                                ->disk('public')
                                ->image()
                                ->avatar()
                                ->imageEditor()
                                ->circleCropper()
                                ->imagePreviewHeight('80')
                                ->placeholder(fn (?User $record) => e(
                                    str($record->name ?? '+')->initials(true)->substr(0, 2),
                                ))
                                ->belowContent(
                                    Actions::make([
                                        Action::make('uploadAvatar')
                                            ->label(__('phpinnacle-cerber::resources.user.hints.avatar'))
                                            ->link()
                                            ->size('sm')
                                            ->alpineClickHandler(
                                                "\$el.closest('[data-field-wrapper]').querySelector('input[type=file]').click()",
                                            ),
                                    ])->alignCenter(),
                                )
                                ->extraFieldWrapperAttributes(['class' => 'cerber-user-avatar-field'])
                                ->extraAttributes(['class' => 'cerber-user-avatar'])
                                ->grow(false),
                            Group::make([
                                TextInput::make('name')
                                    ->label(__('phpinnacle-cerber::resources.user.fields.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(['md' => 2]),
                                Select::make('status')
                                    ->label(__('phpinnacle-cerber::resources.user.fields.status'))
                                    ->options(UserStatus::class)
                                    ->default(UserStatus::Active)
                                    ->enum(UserStatus::class)
                                    ->disabled(fn (?User $record) => $record && $record->id === User::current()->id)
                                    ->selectablePlaceholder(false),
                                TextInput::make('password')
                                    ->label(__('phpinnacle-cerber::pages.profile.fields.password'))
                                    ->password()
                                    ->revealable()
                                    ->minLength(8)
                                    ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                                    ->dehydrated(fn (?string $state) => filled($state))
                                    ->helperText(fn (string $operation) => $operation === 'edit'
                                        ? __('phpinnacle-cerber::resources.user.hints.password')
                                        : null)
                                    ->columnSpan(['md' => 2]),
                                Toggle::make('is_password_temporary')
                                    ->label(__('phpinnacle-cerber::resources.user.fields.is_password_temporary'))
                                    ->helperText(__('phpinnacle-cerber::resources.user.hints.is_password_temporary'))
                                    ->extraFieldWrapperAttributes(['class' => 'cerber-user-temporary'])
                                    ->default(false),
                            ])
                                ->columns(['md' => 3])
                                ->extraAttributes(['class' => 'cerber-user-details']),
                        ])
                            ->from('md')
                            ->verticallyAlignCenter()
                            ->extraAttributes(['class' => 'cerber-user-identity']),
                        Section::make(__('phpinnacle-cerber::resources.user.sections.roles'))
                            ->contained(false)
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
                                        'edit' => $disabled->contains($value)
                                            && !in_array($value, $state, strict: true),
                                        default => false,
                                    })
                                    ->columns(['default' => 1, 'sm' => 2, 'xl' => 4])
                                    ->extraAttributes(['class' => 'cerber-user-roles'])
                                    ->hiddenLabel(),
                            ]),
                    ]),
                Logins::make()->administrative(),
            ]);
    }
}
