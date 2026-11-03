<?php

namespace PHPinnacle\Cerber\Enums;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;
use PHPinnacle\Cerber\Models\Login as LoginModel;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\Services\LoginService;

enum LoginType: string implements HasLabel
{
    case Email = 'email';
    case Phone = 'phone';
    case Login = 'login';
    case OAuth = 'oauth';

    public function getLabel(): string
    {
        return __('phpinnacle-cerber::enums.login_type.' . $this->value);
    }

    public function getCreateAction(User $owner): CreateAction
    {
        return CreateAction::make('add')
            ->label(__('phpinnacle-cerber::logins.add_' . $this->value))
            ->modalHeading(__('phpinnacle-cerber::logins.add_' . $this->value))
            ->model(LoginModel::class)
            ->createAnother(false)
            ->schema([$this->valueField()])
            ->using(function (array $data, LoginService $service) use ($owner) {
                $login = LoginModel::register($owner, $this, $data['value']);
                $service->sendVerification($login);

                return $login;
            });
    }

    /** @return list<Action|ActionGroup> */
    public function getActions(bool $administrative = false): array
    {
        if ($this === self::OAuth) {
            return [DeleteAction::make('delete')->iconButton()->icon(Heroicon::Trash)->keyBindings([])];
        }

        $actions = [
            EditAction::make('edit')
                ->iconButton()
                ->icon(Heroicon::PencilSquare)
                ->color('gray')
                ->modalHeading(__('phpinnacle-cerber::logins.edit'))
                ->modalIcon(Heroicon::PencilSquare)
                ->modalDescription(__('phpinnacle-cerber::logins.edit_description'))
                ->schema(fn (LoginModel $record) => [
                    $this->valueField($record),
                    Toggle::make('is_default')
                        ->label(__('phpinnacle-cerber::logins.default'))
                        ->disabled($record->is_default),
                ])
                ->fillForm(fn (LoginModel $record) => ['value' => $record->value, 'is_default' => $record->is_default])
                ->using(function (LoginModel $record, array $data, LoginService $service) {
                    if ($data['is_default'] ?? false) {
                        $record->is_default = true;
                    }

                    $record->update(['value' => $data['value']]);
                    $service->sendVerification($record);
                }),
            DeleteAction::make('delete')->iconButton()->icon(Heroicon::Trash)->keyBindings([]),
        ];
        $additional = [];

        if ($this === self::Email && $administrative) {
            $additional[] = Action::make('verify')
                ->icon(Heroicon::CheckBadge)
                ->label(__('phpinnacle-cerber::resources.user.actions.mark_as_verified'))
                ->visible(fn (LoginModel $record) => $record->requiresVerification())
                ->requiresConfirmation()
                ->successNotificationTitle(__('phpinnacle-cerber::logins.verified'))
                ->action(function (LoginModel $record, Action $action) {
                    $record->markVerified();
                    $action->success();
                });

            $additional[] = Action::make('dropVerification')
                ->icon(Heroicon::ShieldExclamation)
                ->label(__('phpinnacle-cerber::resources.user.actions.drop_verification'))
                ->visible(fn (LoginModel $record) => $record->verified_at !== null)
                ->requiresConfirmation()
                ->successNotificationTitle(__('phpinnacle-cerber::logins.saved'))
                ->action(function (LoginModel $record, Action $action) {
                    $record->dropVerification();
                    $action->success();
                });

            $additional[] = Action::make('resendVerification')
                ->icon(Heroicon::Envelope)
                ->label(__('phpinnacle-cerber::logins.resend_verification'))
                ->visible(fn (LoginModel $record) => $record->requiresVerification())
                ->successNotificationTitle(__('phpinnacle-cerber::logins.verification_sent'))
                ->action(function (LoginModel $record, LoginService $service, Action $action) {
                    $service->sendVerification($record);
                    $action->success();
                });
            $additional[] = Action::make('resetPassword')
                ->icon(Heroicon::Key)
                ->label(__('phpinnacle-cerber::logins.reset_password'))
                ->requiresConfirmation()
                ->action(function (LoginModel $record, LoginService $service) {
                    $status = $service->resetPassword($record);
                    Notification::make()->title(__($status))->send();
                });
        }

        if ($additional !== []) {
            $actions = [
                ActionGroup::make($additional)
                    ->label(__('phpinnacle-cerber::logins.actions'))
                    ->icon(Heroicon::EllipsisHorizontal)
                    ->color('gray')
                    ->iconButton(),
                ...$actions,
            ];
        }

        return $actions;
    }

    private function valueField(?LoginModel $login = null): TextInput
    {
        return TextInput::make('value')
            ->label(__('phpinnacle-cerber::logins.value'))
            ->required()
            ->maxLength(255)
            ->email($this === self::Email)
            ->rules($this === self::Phone ? ['regex:/^\+[1-9][0-9]{6,14}$/'] : [])
            ->validationMessages(['regex' => __('phpinnacle-cerber::logins.phone_format')])
            ->mutateStateForValidationUsing(fn (?string $state) => LoginModel::canonicalValue($this, $state ?? ''))
            ->unique(
                table: LoginModel::class,
                column: 'value',
                ignorable: $login,
                modifyRuleUsing: fn (Unique $rule) => $rule->where('type', $this->value)->where(
                    'provider',
                    LoginModel::DEFAULT_PROVIDER,
                ),
            );
    }
}
