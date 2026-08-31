<?php

namespace PHPinnacle\Chronica;

use Closure;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPinnacle\Chronica\Timeline\Attribute;
use PHPinnacle\Chronica\Timeline\Relation;
use Spatie\Activitylog\Models\Activity;

class Timeline
{
    private ?string $label = null;

    /**
     * @var array<string, Attribute>
     */
    private array $attributes = [];

    /**
     * @var array<string, Relation>
     */
    private array $relations = [];

    private array $icons = [
        'created' => 'phosphor-plus-circle',
        'updated' => 'phosphor-pencil-simple',
        'deleted' => 'phosphor-trash',
    ];

    private array $iconColors = [
        'created' => 'success',
        'updated' => 'warning',
        'deleted' => 'danger',
    ];

    private bool $searchable = true;

    private bool $descending = true;

    private bool $colorize = true;

    private bool $verbose = true;

    private bool $compact = false;

    public function __construct(
        private readonly string $class,
    ) {}

    public static function make(string $class): self
    {
        return new self($class);
    }

    public function attribute(string $name, Closure $formatter): self
    {
        $this->attributes[$name] = new Attribute($name, formatter: $formatter);

        return $this;
    }

    public function attributes(Attribute ...$attributes): self
    {
        foreach ($attributes as $attribute) {
            $this->attributes[$attribute->name] = $attribute;
        }

        return $this;
    }

    public function colorize(bool $value = true): self
    {
        $this->colorize = $value;

        return $this;
    }

    public function compact(bool $value = true): self
    {
        $this->compact = $value;

        return $this;
    }

    public function descending(bool $value = true): self
    {
        $this->descending = $value;

        return $this;
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function relations(Relation ...$relations): self
    {
        foreach ($relations as $relation) {
            $this->relations[$relation->name] = $relation;
        }

        return $this;
    }

    public function render(Model $record): View
    {
        return view('phpinnacle-chronica::timeline', [
            'compact' => $this->compact,
            'items' => $this->items($record),
            'searchable' => $this->searchable,
            'verbose' => $this->verbose,
        ]);
    }

    public function searchable(bool $value = true): self
    {
        $this->searchable = $value;

        return $this;
    }

    public function verbose(bool $value = true): self
    {
        $this->verbose = $value;

        return $this;
    }

    private function castValue(Activity $activity, string $name, mixed $value): mixed
    {
        $class = $activity->subject_type;
        $model = $activity->subject ?? new $class;
        $model->setRawAttributes([$name => $value]);

        return $model->getAttribute($name);
    }

    /**
     * @return list<array{label: string, new: string, old: string|null}>
     */
    private function changes(Activity $activity): array
    {
        $attributes = $activity->attribute_changes?->get('attributes', []) ?? [];
        $old = $activity->attribute_changes?->get('old', []) ?? [];
        $changes = [];

        foreach (array_unique([...array_keys($old), ...array_keys($attributes)]) as $name) {
            $changes[] = [
                'label' => $this->attributes[$name]->label ?? Str::headline($name),
                'new' => $this->formatValue($activity, $name, $attributes[$name] ?? null),
                'old' => array_key_exists($name, $old) ? $this->formatValue($activity, $name, $old[$name]) : null,
            ];
        }

        return $changes;
    }

    private function formatValue(Activity $activity, string $name, mixed $value): string
    {
        if ($formatter = $this->attributes[$name]->formatter ?? null) {
            $value = $this->castValue($activity, $name, $value);

            return (string) $formatter($value);
        }

        return match (true) {
            $value === null => '—',
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        };
    }

    /**
     * @return Collection<int, array{
     *     causer: string,
     *     changes: list<array{label: string, new: string, old: string|null}>,
     *     color: string|null,
     *     date: string,
     *     datetime: string,
     *     description: string,
     *     icon: string,
     *     model: string,
     *     search: string,
     * }>
     */
    private function items(Model $record): Collection
    {
        /** @var class-string<Activity> $activityModel */
        $activityModel = config('activitylog.activity_model', Activity::class);
        $subjects = $this->subjects($record);

        return $activityModel::query()
            ->with(['causer', 'subject'])
            ->where(function (Builder $query) use ($subjects) {
                foreach ($subjects as $subject) {
                    $query->orWhere(function (Builder $query) use ($subject) {
                        $query
                            ->where('subject_type', $subject->getMorphClass())
                            ->where('subject_id', $subject->getKey());
                    });
                }
            })
            ->orderBy('created_at', $this->descending ? 'desc' : 'asc')
            ->get()
            ->map($this->present(...));
    }

    private function modelLabel(Activity $activity): string
    {
        $class = $this->class;

        if ($activity->subject_type === new $class()->getMorphClass()) {
            return $this->label ?? class_basename($class);
        }

        foreach ($this->relations as $relation) {
            $class = $relation->class;

            if ($activity->subject_type !== new $class()->getMorphClass()) {
                continue;
            }

            $title = match (true) {
                is_string($relation->title) => data_get($activity->subject, $relation->title),
                $relation->title instanceof Closure => ($relation->title)($activity->subject),
                default => null,
            };

            return implode(' · ', array_filter([
                $relation->label ?? class_basename($class),
                $title,
            ]));
        }

        return class_basename($activity->subject_type);
    }

    /**
     * @return array{
     *     causer: string,
     *     changes: list<array{label: string, new: string, old: string|null}>,
     *     color: string|null,
     *     date: string,
     *     datetime: string,
     *     description: string,
     *     icon: string,
     *     model: string,
     *     search: string,
     * }
     */
    private function present(Activity $activity): array
    {
        $event = $activity->event ?? 'activity';
        $changes = $this->changes($activity);
        $causer =
            $activity->causer?->getAttribute('name') ?? $activity->causer?->getAttribute('email') ?? __(
                'phpinnacle-chronica::messages.causer.unknown',
            );
        $model = $this->modelLabel($activity);
        $description = $activity->description;

        return [
            'causer' => (string) $causer,
            'changes' => $changes,
            'color' => $this->colorize ? $this->iconColors[$event] ?? null : null,
            'date' => $activity->created_at?->diffForHumans() ?? '',
            'datetime' => $activity->created_at?->toAtomString() ?? '',
            'description' => $description,
            'icon' => $this->icons[$event] ?? Heroicon::OutlinedArrowsUpDown->value,
            'model' => $model,
            'search' => Str::lower(implode(' ', [
                $description,
                $causer,
                $model,
                ...array_map(fn (array $change) => implode(' ', $change), $changes),
            ])),
        ];
    }

    /**
     * @return Collection<int, Model>
     */
    private function subjects(Model $record): Collection
    {
        $subjects = collect([$record]);

        foreach ($this->relations as $relation) {
            $related = $record->getRelationValue($relation->name);
            $subjects->push(...collect($related instanceof Model ? [$related] : $related)->all());
        }

        return $subjects
            ->filter(fn ($subject) => $subject instanceof Model)
            ->unique(fn (Model $subject) => $subject->getMorphClass() . ':' . $subject->getKey())
            ->values();
    }
}
