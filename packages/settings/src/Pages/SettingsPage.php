<?php

namespace PHPinnacle\Settings\Pages;

use Filament\Actions\Action;
use Filament\Navigation\NavigationGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Concerns\HasUnsavedDataChangesAlert;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Settings\Definition;
use PHPinnacle\Settings\Services\DefinitionRegistry;
use PHPinnacle\Settings\Services\SettingsStorage;
use Throwable;

/**
 * @property Schema $form
 */
class SettingsPage extends Page
{
    use CanUseDatabaseTransactions;
    use HasUnsavedDataChangesAlert;
    use InteractsWithFormActions;

    protected static ?string $slug = 'settings/{group}';

    public string $view = 'phpinnacle-settings::pages.settings';

    public string $group;

    public array $data = [];

    private ?Definition $definition = null;

    public static function canAccess(): bool
    {
        return app(DefinitionRegistry::class)->all()->contains(fn (Definition $definition) => $definition->enabled());
    }

    public static function getNavigationGroup(): string
    {
        return __('phpinnacle-settings::pages.settings.group');
    }

    public static function getNavigationIcon(): string
    {
        return config('phpinnacle-settings.navigation.icon');
    }

    public static function getNavigationLabel(): string
    {
        return __('phpinnacle-settings::pages.settings.label');
    }

    public static function getNavigationSort(): int
    {
        return config('phpinnacle-settings.navigation.sort');
    }

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'settings';
    }

    public static function getUrl(
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?Model $tenant = null,
        bool $shouldGuessMissingParameters = false,
        ?string $configuration = null,
    ): string {
        $parameters['group'] ??= app(DefinitionRegistry::class)->default();

        return parent::getUrl($parameters, $isAbsolute, $panel, $tenant);
    }

    public function form(Schema $schema): Schema
    {
        return $this->getDefinition()->form($schema)->statePath('data');
    }

    public function getHeaderActions(): array
    {
        return [
            $this->getSubmitFormAction(),
        ];
    }

    public function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title(__('phpinnacle-settings::pages.settings.notifications.save.title'))
            ->body(__('phpinnacle-settings::pages.settings.notifications.save.body'))
            ->icon('phosphor-check-circle')
            ->success();
    }

    public function getSubmitFormAction(): Action
    {
        return Action::make('save')
            ->label(__('phpinnacle-settings::pages.settings.actions.save'))
            ->icon('phosphor-check-circle')
            ->action('save')
            ->keyBindings(['mod+s']);
    }

    public function getSubNavigation(): array
    {
        $groups = app(DefinitionRegistry::class)
            ->all()
            ->filter(fn (Definition $definition) => $definition->enabled())
            ->groupBy(fn (Definition $definition) => $definition->parent ?? '');

        $items = $groups->get('', collect())->map(function (Definition $definition) use ($groups) {
            $item = $definition->navigation();
            $children = $groups->get($definition->class, collect());

            return $item->childItems($children->map(fn (Definition $child) => $child->navigation()));
        });

        return [
            NavigationGroup::make()->items($items),
        ];
    }

    public function getTitle(): string
    {
        return sprintf('%s: %s', self::getNavigationLabel(), $this->getDefinition()->label);
    }

    public function mount(string $group): void
    {
        $this->group = $group;
        $this->data = get_mangled_object_vars(app($this->getDefinition()->class));

        $this->form->fill($this->data);
    }

    public function save(SettingsStorage $loader): void
    {
        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');
            $this->callHook('beforeSave');

            $loader->save($this->getDefinition()->class, $data);

            $this->callHook('afterSave');

            $this->commitDatabaseTransaction();
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $this->rollBackDatabaseTransaction()
                : $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->rememberData();
        $this->getSavedNotification()?->send();
    }

    private function getDefinition(): Definition
    {
        return $this->definition ??= app(DefinitionRegistry::class)->get($this->group);
    }
}
