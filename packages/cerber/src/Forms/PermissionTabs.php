<?php

namespace PHPinnacle\Cerber\Forms;

use Filament\Forms\Components\CheckboxList;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Widgets\WidgetConfiguration;
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
                        CheckboxList::make('permissions.' . $section)
                            ->hiddenLabel()
                            ->bulkToggleable()
                            ->options(array_map(fn (string $label) => __($label), $permissions))
                            ->afterStateHydrated(
                                self::adapt(...),
                            )
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

    public static function pages(): array
    {
        $pages = Cerberus::getPages();
        $titles = array_map(fn (string $page) => call_user_func([$page, 'getNavigationLabel']), $pages);

        return [
            Tab::make(__('phpinnacle-cerber::resources.role.sections.pages'))
                ->badge(count($pages))
                ->schema([
                    CheckboxList::make('permissions.pages')
                        ->hiddenLabel()
                        ->bulkToggleable()
                        ->options(array_combine($pages, $titles))
                        ->afterStateHydrated(
                            self::adapt(...),
                        ),
                ])
                ->visible($pages !== []),
        ];
    }

    public static function resources(): array
    {
        return self::groups();
    }

    public static function widgets(): array
    {
        $widgets = array_map(fn (string|WidgetConfiguration $w) => !is_string($w)
            ? $w->widget
            : $w, Cerberus::getWidgets());

        return [
            Tab::make(__('phpinnacle-cerber::resources.role.sections.widgets'))
                ->badge(count($widgets))
                ->schema([
                    CheckboxList::make('permissions.widgets')
                        ->hiddenLabel()
                        ->bulkToggleable()
                        ->options(array_combine($widgets, $widgets))
                        ->afterStateHydrated(
                            self::adapt(...),
                        ),
                ])
                ->visible($widgets !== []),
        ];
    }

    private static function adapt(CheckboxList $component, ?Role $record): void
    {
        $permissions = self::grants($record);

        $component->state(array_intersect($permissions, array_keys($component->getOptions())));
    }

    private static function grants(?Role $role): array
    {
        return once(fn () => $role?->permissions->pluck('name')->all() ?? []);
    }

    private static function groups(): array
    {
        $result = [];
        $other = [];
        $options = Cerberus::getPermissions();
        $prefixes = self::prefixes();

        /** @var class-string<resource> $resource */
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
                    CheckboxList::make('permissions.' . $name)
                        ->hiddenLabel()
                        ->bulkToggleable()
                        ->options(array_combine($permissions, $titles))
                        ->afterStateHydrated(
                            self::adapt(...),
                        )
                        ->columns(4)
                        ->gridDirection('row'),
                ]);

            if ($group !== null) {
                $result[$group][] = $section;
            } else {
                $other[] = $section;
            }
        }

        return collect($result)
            ->put(__('phpinnacle-cerber::permissions.group'), $other)
            ->map(fn (array $sections, string $group) => Tab::make($group)->badge(count($sections))->schema($sections))
            ->values()
            ->all();
    }

    private static function prefixes(): array
    {
        $prefixes = (array) config('phpinnacle-cerber.translations', []);

        return array_replace(array_fill_keys(self::INTERNAL_PREFIXES, 'phpinnacle-cerber::permissions'), $prefixes);
    }
}
