<?php

namespace PHPinnacle\Cerber\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Cerber\Enums\ProviderScope;

/**
 * @implements CastsAttributes<ProviderScope[], ProviderScope[]>
 */
class ProviderScopeCast implements CastsAttributes
{
    /**
     * @param array<string, mixed> $attributes
     * @return ProviderScope[]
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (!is_string($value) || $value === '') {
            return [];
        }

        $scopes = json_decode($value, true);

        if (!is_array($scopes)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $scope) => is_string($scope)
                ? ProviderScope::tryFrom($scope)
                : null,
            $scopes,
        )));
    }

    /**
     * @param array<string, mixed> $attributes
     * @param ProviderScope[] $value
     * @throws \JsonException
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $scopes = array_map(
            fn (ProviderScope $scope) => $scope->value,
            $value,
        );

        return json_encode(
            array_values(array_unique($scopes)),
            JSON_THROW_ON_ERROR,
        );
    }
}
