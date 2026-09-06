<?php

namespace PHPinnacle\Porta\Models;

use Carbon\CarbonImmutable;
use Closure;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;
use JsonPath\JsonObject;
use PHPinnacle\Intl\Locales;
use PHPinnacle\Porta\Enums\TransformType;
use Throwable;

/**
 * @phpstan-import-type TransformConfig from TransformType
 * @phpstan-type TransformData array{path?: string, type: string|TransformType, config?: TransformConfig}
 */
readonly class Transform
{
    /**
     * @param TransformConfig $config
     */
    public function __construct(
        private string $path,
        private TransformType $type,
        private array $config = [],
    ) {}

    /**
     * @param TransformData $data
     */
    public static function make(array $data): self
    {
        $path = $data['path'] ?? '$';

        if (!str_starts_with($path, '$')) {
            $path = '$.' . $path;
        }

        return new self($path, TransformType::resolve($data['type']), $data['config'] ?? []);
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    public function apply(array $data): array
    {
        return match ($this->type) {
            TransformType::DROP => $this->drop($data),
            TransformType::MOVE => $this->move($data),
            default => $this->update($data, $this->path, match ($this->type) {
                TransformType::MAP => $this->mapValue(...),
                TransformType::CAST => $this->castValue(...),
                TransformType::TRIM => $this->trimValue(...),
                TransformType::DATE => $this->dateValue(...),
                TransformType::RENAME => $this->renameValue(...),
                TransformType::INSERT => $this->insertValue(...),
            }),
        };
    }

    private function castValue(mixed $value): mixed
    {
        settype($value, $this->config['type'] ?? 'string');

        return $value;
    }

    private function dateValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $input = $this->config['input'] ?? '';
        $output = $this->config['output'] ?? DateTimeInterface::ATOM;
        $modify = $this->config['modify'] ?? '';
        $locale = $this->config['locale'] ?? Locales::default();

        try {
            $date = filled($input)
                ? CarbonImmutable::createFromLocaleFormat($input, $locale, $value)
                : CarbonImmutable::parseFromLocale($value, $locale);

            if (filled($modify)) {
                $date = $date->modify($modify);
            }

            if ($this->config['future'] ?? false) {
                $date = $date < Date::now() ? $date->addYear() : $date;
            }

            return $date->format($output);
        } catch (Throwable) {
            return match ($this->config['behavior'] ?? 'skip') {
                'now' => Date::now()->format($output),
                'null' => null,
                default => $value,
            };
        }
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    private function drop(array $data): array
    {
        $parts = explode('.', $this->path);
        $field = array_pop($parts);

        if ($field === '') {
            return $data;
        }

        return $this->update($data, implode('.', $parts), function (mixed $value) use ($field) {
            if (is_array($value)) {
                unset($value[$field]);
            }

            return $value;
        });
    }

    private function insertValue(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($this->config['fields'] ?? [] as $key => $item) {
                $value[$key] = $item;
            }
        }

        return $value;
    }

    private function mapValue(mixed $value): mixed
    {
        $type = gettype($value);

        if (!in_array($type, ['boolean', 'bool', 'integer', 'int', 'float', 'double', 'string'], strict: true)) {
            return $value;
        }

        $map = $this->config['fields'] ?? [];

        if (($map[(string) $value] ?? null) !== null) {
            $value = $map[$value];
        } elseif (($map['*'] ?? null) !== null) {
            $value = $map['*'];
        }

        settype($value, $type);

        return $value;
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    private function move(array $data): array
    {
        $json = new JsonObject($data);
        $values = $json->get($this->path);

        if (!is_array($values)) {
            return $data;
        }

        foreach ($values as $value) {
            $json->set($this->config['dest'] ?? '$', $value);
        }

        return $this->drop((array) $json->getValue());
    }

    private function renameValue(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($this->config['fields'] ?? [] as $from => $to) {
            if (!array_key_exists($from, $value)) {
                continue;
            }

            $value[$to] = $value[$from];

            unset($value[$from]);
        }

        return $value;
    }

    private function trimValue(mixed $value): mixed
    {
        $chars = $this->config['chars'] ?? '';

        return is_string($value) ? trim($value, $chars !== '' ? $chars : " \n\r\t\v\0") : $value;
    }

    /**
     * @param array<array-key, mixed> $data
     * @param Closure(mixed): mixed $transform
     * @return array<array-key, mixed>
     */
    private function update(array $data, string $path, Closure $transform): array
    {
        $json = new JsonObject($data);
        $values = $json->get($path);

        if (!is_array($values)) {
            return $data;
        }

        foreach ($values as &$value) {
            $value = $transform($value);
        }

        return (array) $json->getValue();
    }
}
