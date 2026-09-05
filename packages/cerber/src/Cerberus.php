<?php

namespace PHPinnacle\Cerber;

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use PHPinnacle\Cerber\Contracts\HasCustomPermissions;
use PHPinnacle\Cerber\Models\Invitation;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Models\Tenant;

class Cerberus
{
    private const array PREFIXES = [
        'create',
        'update',
        'view',
        'view_any',
        'delete',
        'delete_any',
    ];

    public static array $scoped = [
        Role::class => Role::class,
        Invitation::class => Invitation::class,
        Provider::class => Provider::class,
    ];

    public static array $permissions = [];

    public static function tenant(): string
    {
        return Filament::getTenant()?->getKey() ?? Tenant::DEFAULT;
    }

    public static function guard(string $group, string $section, array $permissions): void
    {
        self::$permissions[$group][$section] = $permissions;
    }

    public static function registerToScope(string ...$models): void
    {
        foreach ($models as $model) {
            if (!is_a($model, Model::class, true)) {
                throw new InvalidArgumentException(sprintf('Model %s must be an instance of %s', $model, Model::class));
            }

            self::$scoped[$model] = $model;
        }
    }

    public static function registerScoped(): void
    {
        /** @var class-string<Model> $model */
        foreach (self::$scoped as $model) {
            $model::creating(function (Model $record) {
                if ($record->getAttribute('tenant_id') === null) {
                    $record->setAttribute('tenant_id', self::tenant());
                }
            });

            $model::resolveRelationUsing('tenant', fn (Model $record) => $record->belongsTo(
                Tenant::class,
                'tenant_id',
            ));
        }
    }

    public static function getGuards(): array
    {
        $result = [];

        foreach (self::$permissions as $sections) {
            foreach ($sections as $permissions) {
                $result = [
                    ...$result,
                    ...array_keys($permissions),
                ];
            }
        }

        return array_unique($result);
    }

    public static function getPages(?Panel $panel = null): array
    {
        $panel ??= Filament::getCurrentPanel();

        return array_diff($panel?->getPages() ?? [], config('phpinnacle-cerber.exclude.pages', []));
    }

    public static function getWidgets(?Panel $panel = null): array
    {
        $panel ??= Filament::getCurrentPanel();

        $widgets = array_map(
            fn (string|WidgetConfiguration $v) => $v instanceof WidgetConfiguration ? $v->widget : $v,
            $panel?->getWidgets() ?? [],
        );

        return array_diff($widgets, config('phpinnacle-cerber.exclude.widgets', []));
    }

    public static function getPermissions(?Panel $panel = null, bool $flatten = false): array
    {
        $panel ??= Filament::getCurrentPanel();
        /** @var array<class-string<Resource>> $resources */
        $resources = array_diff($panel?->getResources() ?? [], config('phpinnacle-cerber.exclude.resources', []));
        $resources = array_filter($resources, fn (string $r) => !$r::shouldSkipAuthorization());
        $permissions = [];

        usort($resources, fn ($a, $b) => $a::getNavigationSort() <=> $b::getNavigationSort());

        foreach ($resources as $resource) {
            $permissions[$resource] = self::getResourcePermissions($resource);
        }

        return $flatten ? Arr::flatten($permissions) : $permissions;
    }

    private static function getResourcePermissions(string $resource): array
    {
        $config = config('phpinnacle-cerber.permissions');
        $prefix = str(call_user_func([$resource, 'getModel']))->afterLast('\\')->snake();
        $permissions = is_a($resource, HasCustomPermissions::class, true)
            ? $resource::getPermissions()
            : $config[$resource] ?? self::PREFIXES;

        return array_map(fn (string $p) => sprintf('%s_%s', $p, $prefix), $permissions);
    }
}
