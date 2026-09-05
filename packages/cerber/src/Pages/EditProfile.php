<?php

namespace PHPinnacle\Cerber\Pages;

use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile as BasePage;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Panel;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use LogicException;
use PHPinnacle\Cerber\CerberPlugin;
use PHPinnacle\Cerber\Forms\AvatarUpload;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Models\SocialAccount;
use PHPinnacle\Cerber\Services\ProviderRegistry;

class EditProfile extends BasePage
{
    protected static bool $shouldRegisterNavigation = false;

    public static function isSimple(): bool
    {
        return false;
    }

    public static function getRouteName(?Panel $panel = null): string
    {
        $panel ??= Filament::getCurrentOrDefaultPanel();

        return sprintf('filament.%s.profile', $panel?->getId() ?? 'default');
    }

    public function form(Schema $schema): Schema
    {
        return CerberPlugin::get()->doModifyProfileForm($schema, $this);
    }

    public function content(Schema $schema): Schema
    {
        $components = [
            $this->getFormContentComponent(),
        ];

        $mfa = $this->getMultiFactorAuthenticationContentComponent();

        if ($mfa !== null) {
            $components[] = $mfa;
        }

        $oauth = $this->getOAuthAccountsComponent();

        if ($oauth !== null) {
            $components[] = $oauth;
        }

        return $schema->components($components);
    }

    public function getProfileContentComponent(): ?Component
    {
        return Section::make()
            ->heading(__('phpinnacle-cerber::pages.profile.sections.general'))
            ->description(__('phpinnacle-cerber::pages.profile.descriptions.general'))
            ->aside()
            ->schema([
                Flex::make([])
                    ->schema([
                        $this->getAvatarFormComponent(),
                        Group::make()
                            ->schema([
                                $this->getNameFormComponent()
                                    ->inlineLabel(false),
                                $this->getEmailFormComponent()
                                    ->inlineLabel(false),
                            ]),
                    ]),
            ]);
    }

    public function getPasswordContentComponent(): ?Component
    {
        return Section::make()
            ->heading(__('phpinnacle-cerber::pages.profile.sections.password'))
            ->description(__('phpinnacle-cerber::pages.profile.descriptions.password'))
            ->aside()
            ->schema([
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }

    public function getMultiFactorAuthenticationContentComponent(): ?Component
    {
        $section = parent::getMultiFactorAuthenticationContentComponent();

        if (!$section instanceof Section) {
            throw new LogicException('The multi-factor authentication component must be a section.');
        }

        return $section
            ->aside()
            ->heading(__('phpinnacle-cerber::pages.profile.sections.two_factor'))
            ->description(__('phpinnacle-cerber::pages.profile.descriptions.two_factor'))
            ->secondary(false)
            ->label('');
    }

    public function getAvatarFormComponent(): FileUpload
    {
        return AvatarUpload::make('avatar');
    }

    public function getOAuthAccountsComponent(): ?Component
    {
        $linked = SocialAccount::linked(Filament::auth()->user());
        $components = [];

        foreach (Provider::valid() as $provider) {
            $isLinked = in_array($provider->getKey(), $linked, strict: true);

            $components[] = Actions::make(
                fn (ProviderRegistry $registry) => $this->oauthActions($registry, $provider, $isLinked),
            )
                ->label($provider->getLabel())
                ->afterLabel(
                    fn () => $isLinked
                        ? Text::make(__('phpinnacle-cerber::resources.accounts.linked'))
                            ->badge()
                            ->color('success')
                        : Text::make(__('phpinnacle-cerber::resources.accounts.not_linked'))
                            ->badge(),
                );
        }

        if ($components === []) {
            return null;
        }

        return Section::make()
            ->heading(__('phpinnacle-cerber::pages.profile.sections.oauth_accounts'))
            ->description(__('phpinnacle-cerber::pages.profile.descriptions.oauth_accounts'))
            ->schema($components)
            ->divided()
            ->aside();
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::End;
    }

    /**
     * @return list<Action>
     */
    private function oauthActions(ProviderRegistry $registry, Provider $provider, bool $linked): array
    {
        $icon = $registry->get($provider->type)->getIcon();

        return [
            Action::make(sprintf('link:%s', $provider->getKey()))
                ->label(__('phpinnacle-cerber::resources.accounts.actions.link'))
                ->icon($icon)
                ->color('primary')
                ->visible(!$linked)
                ->url(Filament::getCurrentPanel()->route('auth.link', ['provider' => $provider->getKey()]))
                ->link(),
            Action::make(sprintf('unlink:%s', $provider->getKey()))
                ->label(__('phpinnacle-cerber::resources.accounts.actions.unlink'))
                ->icon($icon)
                ->color('danger')
                ->visible($linked)
                ->action(fn () => SocialAccount::unlink(Filament::auth()->user(), $provider))
                ->requiresConfirmation()
                ->link(),
        ];
    }
}
