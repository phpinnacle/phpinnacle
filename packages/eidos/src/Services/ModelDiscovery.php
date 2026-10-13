<?php

namespace PHPinnacle\Eidos\Services;

use Composer\Autoload\ClassLoader;
use FilesystemIterator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

class ModelDiscovery
{
    /**
     * @param list<string> $include Model class names and discovery wildcard patterns.
     * @param list<string> $except Discovery wildcard patterns to exclude.
     * @return list<class-string<Model>>
     */
    public function modelClasses(array $include, array $except): array
    {
        /** @var list<class-string<Model>> $explicitModels */
        $explicitModels = [];
        $patterns = [];

        foreach ($include as $name) {
            if (str_contains($name, '*')) {
                $patterns[] = $name;
            } else {
                $explicitModels[] = $name;
            }
        }

        if ($patterns === []) {
            return array_values(array_unique($explicitModels));
        }

        $classes = [];

        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            array_push($classes, ...array_keys($loader->getClassMap()));

            foreach ($loader->getPrefixesPsr4() as $namespace => $directories) {
                if (!$this->includesNamespace($namespace, $patterns)) {
                    continue;
                }

                foreach ($directories as $directory) {
                    if (!is_dir($directory)) {
                        continue;
                    }

                    $directory = rtrim($directory, '/\\');
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                    );

                    foreach ($files as $file) {
                        if (!$file->isFile() || $file->getExtension() !== 'php') {
                            continue;
                        }

                        $relativePath = substr($file->getPathname(), strlen($directory) + 1, -4);
                        $classes[] = $namespace . str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
                    }
                }
            }
        }

        $discoveredModels = array_filter(
            array_unique($classes),
            fn (string $class) => (
                Str::is($patterns, $class)
                && !Str::is($except, $class)
                && is_subclass_of($class, Model::class)
                && new ReflectionClass($class)->isInstantiable()
            ),
        );

        return array_values(array_unique([...$explicitModels, ...$discoveredModels]));
    }

    /** @param list<string> $include */
    private function includesNamespace(string $namespace, array $include): bool
    {
        return array_any($include, function (string $pattern) use ($namespace) {
            $prefix = explode('*', $pattern, 2)[0];

            return str_starts_with($namespace, $prefix) || str_starts_with($prefix, $namespace);
        });
    }
}
