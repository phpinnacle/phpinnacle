<?php

namespace PHPinnacle\Eidos\Commands;

use Illuminate\Console\Command;
use PHPinnacle\Eidos\Services\EloquentIntrospector;
use PHPinnacle\Eidos\Services\ModelRegistry;

class WarmEloquentSchemas extends Command
{
    protected $signature = 'eidos:cache-eloquent-schemas';

    protected $description = 'Inspect and cache the schemas of models from configured Eidos sources';

    public function handle(ModelRegistry $sources, EloquentIntrospector $introspector): int
    {
        $models = $sources->modelClasses();

        foreach ($models as $modelClass) {
            $introspector->warm($modelClass);
        }

        $this->components->info(sprintf('Cached schemas for %d Eloquent model(s).', count($models)));

        return self::SUCCESS;
    }
}
