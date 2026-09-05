<?php

namespace PHPinnacle\Tags;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Tags\Models\Tag;

class TagsPlugin implements Plugin
{
    use EvaluatesClosures;

    private array $models = [];

    public static function get(): static
    {
        // @mago-expect lint:inline-variable-return
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function boot(Panel $panel): void
    {
        foreach ($this->models as $model) {
            $class = $this->evaluate($model);

            if (!class_exists($class) || !is_subclass_of($class, Model::class)) {
                continue;
            }

            $class::resolveRelationUsing(
                'tags',
                fn (Model $record) => $record->belongsToMany(Tag::class, 'taggables', 'taggable_id'),
            );

            $class::deleted(function (Model $record) {
                Tag::clear($record);
            });
        }
    }

    public function getId(): string
    {
        return 'phpinnacle/tags';
    }

    public function models(Closure|string ...$models): self
    {
        $this->models = $models;

        return $this;
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            Resources\Tags\TagResource::class,
        ]);
    }
}
