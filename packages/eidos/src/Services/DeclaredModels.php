<?php

namespace PHPinnacle\Eidos\Services;

use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Eidos\Contracts\ProvidesEloquentModels;
use ReflectionClass;

class DeclaredModels implements ProvidesEloquentModels
{
    /** @param list<string> $except */
    public function __construct(
        #[Config('phpinnacle-eidos.model_discovery.except', [])]
        private array $except = [],
    ) {}

    /** @return list<class-string<Model>> */
    public function modelClasses(): array
    {
        return array_values(array_filter(get_declared_classes(), function (string $class) {
            if (!is_subclass_of($class, Model::class)) {
                return false;
            }

            $reflection = new ReflectionClass($class);

            return $reflection->isInstantiable()
            && $reflection->getShortName() !== 'Model'
            && array_all($this->except, fn (string $except) => !str_starts_with($class, $except));
        }));
    }
}
