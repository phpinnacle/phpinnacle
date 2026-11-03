<?php

namespace PHPinnacle\Cerber\Pages;

use Filament\Auth\Pages\EditProfile as BasePage;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Panel;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use PHPinnacle\Cerber\CerberPlugin;
use PHPinnacle\Cerber\Forms\AvatarUpload;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\Schemas\Logins;

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
        return CerberPlugin::get()->doModifyProfileForm($schema->model(User::current()), $this);
    }

    public function content(Schema $schema): Schema
    {
        $components = [
            Section::make(__('phpinnacle-cerber::pages.profile.sections.general'))
                ->aside()
                ->description(__('phpinnacle-cerber::pages.profile.descriptions.general'))
                ->schema([$this->getFormContentComponent()]),
            Logins::make()->aside(),
        ];

        $mfa = $this->getMultiFactorAuthenticationContentComponent();

        if ($mfa !== null) {
            $components[] = $mfa;
        }

        return $schema->model(User::current())->components($components);
    }

    public function getProfileContentComponent(): ?Component
    {
        return Flex::make([])
            ->verticalAlignment(VerticalAlignment::Center)
            ->schema([
                $this->getAvatarFormComponent(),
                Group::make()
                    ->schema([
                        $this->getNameFormComponent()->inlineLabel(false),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ]),
            ]);
    }

    public function getMultiFactorAuthenticationContentComponent(): ?Component
    {
        /** @var Section|null $section */
        $section = parent::getMultiFactorAuthenticationContentComponent();

        return $section
            ?->aside()
            ->heading(__('phpinnacle-cerber::pages.profile.sections.two_factor'))
            ->description(__('phpinnacle-cerber::pages.profile.descriptions.two_factor'))
            ->secondary(false)
            ->label('');
    }

    public function getAvatarFormComponent(): FileUpload
    {
        return AvatarUpload::make('avatar');
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::End;
    }
}
