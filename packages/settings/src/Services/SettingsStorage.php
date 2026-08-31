<?php

namespace PHPinnacle\Settings\Services;

use BackedEnum;
use Filament\Facades\Filament;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\DB;
use LogicException;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Throwable;

#[Singleton]
class SettingsStorage
{
    private const array BUILTIN_TYPES = [
        'bool',
        'boolean',
        'int',
        'integer',
        'float',
        'double',
        'string',
        'array',
        'object',
        'null',
    ];

    private const string TENANT = '00000000-0000-0000-0000-000000000000';

    private array $settings = [];

    private array $schema = [];

    public function fill(object $settings): void
    {
        $data = $this->load($settings::class);

        foreach ($data as $key => $value) {
            $settings->{$key} = $value;
        }
    }

    public function load(string $section): array
    {
        return $this->doLoad($section);
    }

    public function register(string ...$sections): void
    {
        foreach ($sections as $section) {
            if (isset($this->schema[$section])) {
                continue;
            }

            $reflection = new ReflectionClass($section);
            $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($properties as $property) {
                $type = $property->getType();

                if ($type !== null && !$type instanceof ReflectionNamedType) {
                    throw new LogicException(sprintf(
                        'Setting [%s::%s] must declare a single named type.',
                        $section,
                        $property->getName(),
                    ));
                }

                $this->schema[$section][$property->getName()] = [
                    'type' => $type?->getName(),
                    'null' => $type?->allowsNull() ?? false,
                ];
            }
        }
    }

    public function save(string $section, array $data): void
    {
        $tenant = Filament::hasTenancy() ? Filament::getTenant()->getKey() : self::TENANT;

        foreach ($data as $key => $value) {
            $group = Uuid::uuid5(Uuid::NAMESPACE_URL, sprintf('phpinnacle://%s/settings/%s', $tenant, $section));

            DB::table('settings')->upsert([
                'id' => Uuid::uuid5($group, $key)->toString(),
                'tenant_id' => $tenant,
                'group' => $section,
                'key' => $key,
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
            ], ['id']);
        }

        $this->settings[$tenant][$section] = $data;
    }

    private function coerse(string $section, string $key, mixed $value): mixed
    {
        $type = $this->schema[$section][$key]['type'] ?? null;

        if ($type === null) {
            return $value;
        }

        if (is_subclass_of($type, BackedEnum::class) && (is_string($value) || is_int($value))) {
            $value = $type::tryFrom($value);
        } elseif (in_array($type, self::BUILTIN_TYPES, true)) {
            settype($value, $type);
        }

        return $value;
    }

    private function doLoad(string $section): array
    {
        $tenant = Filament::hasTenancy() ? Filament::getTenant()->getKey() : self::TENANT;

        if (!isset($this->settings[$tenant][$section])) {
            $this->init();
        }

        return $this->settings[$tenant][$section] ?? [];
    }

    private function init(): void
    {
        try {
            $settings = DB::table('settings')->get()->all();
        } catch (Throwable) {
            $settings = [];
        }

        foreach ($settings as $setting) {
            $this->register($setting->group);

            $value = $this->coerse($setting->group, $setting->key, json_decode($setting->value, true));

            $this->settings[$setting->tenant_id][$setting->group][$setting->key] = $value;
        }
    }
}
