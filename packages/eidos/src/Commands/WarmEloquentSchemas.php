<?php

namespace PHPinnacle\Eidos\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Eidos\Services\SchemaFactory;

class WarmEloquentSchemas extends Command
{
    protected $signature = 'eidos:cache-eloquent-schemas';

    protected $description = 'Discover configured Eloquent models and cache their schemas';

    public function handle(SchemaFactory $factory): int
    {
        /** @var list<class-string<Model>> $models */
        $models = config('phpinnacle-eidos.models');
        /** @var list<string> $include */
        $include = config('phpinnacle-eidos.discovery.include');
        /** @var list<string> $except */
        $except = config('phpinnacle-eidos.discovery.except');
        $count = $factory->warmup([...$models, ...$include], $except);
        $this->components->info(sprintf('Cached schemas for %d Eloquent model(s).', $count));

        return self::SUCCESS;
    }
}
