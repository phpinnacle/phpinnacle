<?php

namespace PHPinnacle\Cerber\Schemas;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Support\Icons\Heroicon;
use PHPinnacle\Cerber\CerberPlugin;
use PHPinnacle\Cerber\Enums\LoginType;
use PHPinnacle\Cerber\Models\Login;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\Services\ProviderRegistry;

class Logins extends Section
{
    protected bool $isAdministrative = false;

    public function administrative(bool $condition = true): static
    {
        $this->isAdministrative = $condition;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->key('logins')
            ->heading(__('phpinnacle-cerber::logins.label'))
            ->description(__('phpinnacle-cerber::logins.description'))
            ->divided()
            ->visible(fn (?User $record) => $record->exists ?? false)
            ->schema(fn (?User $record) => $record?->exists ? $this->groups($record) : []);
    }

    /** @return list<Section> */
    private function groups(User $user): array
    {
        $logins = $user
            ->logins()
            ->with('provider')
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (Login $login) => $login->type->value);
        $groups = [];

        foreach (LoginType::cases() as $type) {
            $rows = [];
            foreach ($logins->get($type->value, []) as $login) {
                $actions = $type->getActions($this->isAdministrative);
                foreach ($actions as $action) {
                    $action->record($login)->recordTitle($login->value);
                    foreach ($action instanceof ActionGroup ? $action->getFlatActions() : [$action] as $itemAction) {
                        $this->configureAction($itemAction, $user);
                    }
                }

                $rows[] = Flex::make([
                    Text::make(
                        $type === LoginType::OAuth
                            ? $login->provider->getLabel() . ': ' . ($login->metadata['email'] ?? $login->value)
                            : $login->value,
                    )->weight(FontWeight::Medium),
                    Text::make(
                        $login->requiresVerification()
                            ? __('phpinnacle-cerber::logins.unverified')
                            : __('phpinnacle-cerber::logins.verified'),
                    )
                        ->badge()
                        ->color($login->requiresVerification() ? 'warning' : 'success')
                        ->visible($type === LoginType::Email)
                        ->grow(false),
                    Text::make(__('phpinnacle-cerber::logins.default'))
                        ->badge()
                        ->color('gray')
                        ->visible($login->is_default)
                        ->grow(false),
                    Actions::make($actions)->key($login->id)->grow(false),
                ])->verticalAlignment(VerticalAlignment::Center)->from('sm');
            }

            if ($type === LoginType::OAuth) {
                if (!$this->isAdministrative && ($context = CerberPlugin::get()->getSocialAccountContext()) !== null) {
                    $linked = $user->linkedProviders($context);
                    foreach (Provider::valid($context) as $provider) {
                        if (in_array($provider->id, $linked, strict: true)) {
                            continue;
                        }

                        $rows[] = Actions::make([Action::make('link:' . $provider->id)
                            ->label($provider->getLabel())
                            ->icon(app(ProviderRegistry::class)->get($provider->type)->getIcon())
                            ->url(Filament::getCurrentPanel()->route('auth.link', ['provider' => $provider->id]))
                            ->link()])->key($provider->id);
                    }
                }

                if ($rows === []) {
                    continue;
                }
            } else {
                $rows[] = Actions::make([$this
                    ->configureAction($type->getCreateAction($user), $user)
                    ->link()
                    ->icon(Heroicon::Plus)])->key('add');
            }

            $groups[] = Section::make(__('phpinnacle-cerber::logins.groups.' . $type->value))
                ->key($type->value)
                ->contained(false)
                ->compact()
                ->schema($rows);
        }

        return $groups;
    }

    private function configureAction(Action $action, User $owner): Action
    {
        return $action
            ->authorize(fn () => $this->isAdministrative
                ? Filament::auth()->user()->can('update_user')
                : User::current()->id === $owner->id);
    }
}
