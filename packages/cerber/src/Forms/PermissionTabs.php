<?php

namespace PHPinnacle\Cerber\Forms;

use Filament\Forms\Components\CheckboxList;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Str;
use PHPinnacle\Cerber\Cerberus;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Resources;

class PermissionTabs
{
    private const array INTERNAL_PREFIXES = [
        Resources\Providers\ProviderResource::class,
        Resources\Roles\RoleResource::class,
        Resources\Users\UserResource::class,
    ];

    public static function make(): Tabs
    {
        $permissions = Cerberus::$permissions;
        $tabs = [
            ...self::resources(),
            ...self::widgets(),
            ...self::pages(),
        ];

        foreach ($permissions as $tab => $sections) {
            $schema = [];

            foreach ($sections as $section => $permissions) {
                $schema[] = Section::make(__($section))
                    ->schema([
                        self::checkboxList($section)
                            ->options(array_map(fn (string $label) => __($label), $permissions))
                            ->columns(4)
                            ->gridDirection('row'),
                    ]);
            }

            $label = __($tab);
            $existingTab = collect($tabs)->first(fn (Tab $tab) => $tab->getLabel() === $label);

            if ($existingTab !== null) {
                $schema = [...$existingTab->getDefaultChildComponents(), ...$schema];
                $existingTab->badge(count($schema))->schema($schema)->visible();

                continue;
            }

            $tabs[] = Tab::make($label)
                ->badge(count($sections))
                ->schema($schema);
        }

        foreach ($tabs as $index => $tab) {
            $tab->key("permission-tab-{$index}", isInheritable: false);
        }

        return Tabs::make()
            ->vertical()
            ->tabs($tabs)
            ->columnSpanFull();
    }

    /**
     * @return list<Tab>
     */
    public static function resources(): array
    {
        return self::groups();
    }

    /**
     * @return list<Tab>
     */
    public static function widgets(): array
    {
        $widgets = Cerberus::getWidgets();

        return [
            Tab::make(__('phpinnacle-cerber::resources.role.sections.widgets'))
                ->badge(count($widgets))
                ->schema([
                    self::checkboxList('widgets')
                        ->options(array_combine($widgets, $widgets)),
                ])
                ->visible($widgets !== []),
        ];
    }

    /**
     * @return list<Tab>
     */
    public static function pages(): array
    {
        $pages = Cerberus::getPages();
        $titles = array_map(fn (string $page) => $page::getNavigationLabel(), $pages);

        return [
            Tab::make(__('phpinnacle-cerber::resources.role.sections.pages'))
                ->badge(count($pages))
                ->schema([
                    self::checkboxList('pages')
                        ->options(array_combine($pages, $titles)),
                ])
                ->visible($pages !== []),
        ];
    }

    /**
     * @return list<Tab>
     */
    private static function groups(): array
    {
        $result = [];
        $other = [];
        $options = Cerberus::getPermissions();
        $prefixes = self::prefixes();

        /** @var class-string<Resource> $resource */
        foreach ($options as $resource => $permissions) {
            $label = $resource::getNavigationLabel();
            $parent = $resource::getParentResource();
            $group = $resource::getNavigationGroup() ?? ($parent !== null ? $parent::getNavigationGroup() : null);

            $prefix = $prefixes[$resource] ?? 'permissions';
            $model = $resource::getModel();
            $name = Str::snake(Str::afterLast($model, '\\'));
            $titles = array_map(
                fn (string $permission) => __(sprintf(
                    '%s.%s.%s',
                    $prefix,
                    $name,
                    str_replace('_' . $name, '', $permission),
                )),
                $permissions,
            );

            $section = Section::make($label)
                ->description($model)
                ->schema([
                    self::checkboxList($name)
                        ->options(array_combine($permissions, $titles))
                        ->columns(4)
                        ->gridDirection('row'),
                ]);

            if ($group !== null) {
                $result[$group][] = $section;
            } else {
                $other[] = $section;
            }
        }

        $result[__('phpinnacle-cerber::permissions.group')] = $other;
        $tabs = [];

        foreach ($result as $group => $sections) {
            $tabs[] = Tab::make($group)->badge(count($sections))->schema($sections);
        }

        return $tabs;
    }

    private static function adapt(CheckboxList $component, ?Role $record): void
    {
        $permissions = $record?->permissions->pluck('name')->all() ?? [];

        $component->state(array_intersect($permissions, array_keys($component->getOptions())));
    }

    private static function checkboxList(string $name): CheckboxList
    {
        return CheckboxList::make('permissions.' . $name)
            ->hiddenLabel()
            ->bulkToggleable()
            ->afterStateHydrated(self::adapt(...));
    }

    /**
     * @return array<class-string<Resource>, string>
     */
    private static function prefixes(): array
    {
        $prefixes = (array) config('phpinnacle-cerber.translations', []);

        return array_replace(array_fill_keys(self::INTERNAL_PREFIXES, 'phpinnacle-cerber::permissions'), $prefixes);
    }
}
