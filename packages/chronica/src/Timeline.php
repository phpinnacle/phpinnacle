<?php

namespace PHPinnacle\Chronica;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use PHPinnacle\Chronica\Timeline\Attribute;
use PHPinnacle\Chronica\Timeline\Relation;
use Spatie\Activitylog\Models\Activity;
use TypeError;
use ValueError;

/** @mago-expect lint:too-many-properties */
class Timeline
{
    private ?string $label = null;

    /**
     * @var array<string, Attribute>
     */
    private array $attributes = [];

    /**
     * @var list<string>
     */
    private array $excludedAttributes = [];

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

    private bool $hideEmptyValues = false;

    private bool $boolean = false;

    private bool $revertable = false;

    private string|BackedEnum $trueIcon = Heroicon::OutlinedCheckCircle;

    private string|BackedEnum $falseIcon = Heroicon::OutlinedXCircle;

    private string $trueColor = 'success';

    private string $falseColor = 'danger';

    private string|BackedEnum|null $nullIcon = null;

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

    public function boolean(bool $value = true): self
    {
        $this->boolean = $value;

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

    public function exclude(string ...$attributes): self
    {
        $this->excludedAttributes = [...$this->excludedAttributes, ...$attributes];

        return $this;
    }

    public function falseColor(string $color): self
    {
        $this->boolean = true;
        $this->falseColor = $color;

        return $this;
    }

    public function falseIcon(string|BackedEnum $icon): self
    {
        $this->boolean = true;
        $this->falseIcon = $icon;

        return $this;
    }

    public function hideEmptyValues(bool $value = true): self
    {
        $this->hideEmptyValues = $value;

        return $this;
    }

    public function isRevertable(): bool
    {
        return $this->revertable;
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function nullIcon(string|BackedEnum|null $icon = Heroicon::OutlinedMinusCircle): self
    {
        $this->nullIcon = $icon;

        return $this;
    }

    public function relations(Relation ...$relations): self
    {
        foreach ($relations as $relation) {
            $this->relations[$relation->name] = $relation;
        }

        return $this;
    }

    public function render(Model $record, ?Action $revertAction = null): View
    {
        return view('phpinnacle-chronica::timeline', [
            'compact' => $this->compact,
            'items' => $this->items($record),
            'revertAction' => $revertAction,
            'searchable' => $this->searchable,
            'verbose' => $this->verbose,
        ]);
    }

    public function revert(Model $record, int|string $activityId): void
    {
        abort_unless($this->revertable, 404);

        /** @var Activity $activity */
        $activity = $this
            ->activityQuery($record)
            ->with('subject')
            ->where('event', 'updated')
            ->findOrFail($activityId);
        $subject = $activity->subject;
        $old = $activity->attribute_changes?->get('old', []) ?? [];

        abort_if($subject === null || $old === [], 404);
        Gate::authorize('update', $subject);

        $subject->getConnection()->transaction(fn () => $subject->forceFill($old)->save());
    }

    public function revertable(bool $value = true): self
    {
        $this->revertable = $value;

        return $this;
    }

    public function searchable(bool $value = true): self
    {
        $this->searchable = $value;

        return $this;
    }

    public function trueColor(string $color): self
    {
        $this->boolean = true;
        $this->trueColor = $color;

        return $this;
    }

    public function trueIcon(string|BackedEnum $icon): self
    {
        $this->boolean = true;
        $this->trueIcon = $icon;

        return $this;
    }

    public function verbose(bool $value = true): self
    {
        $this->verbose = $value;

        return $this;
    }

    /**
     * @return Builder<Activity>
     */
    private function activityQuery(Model $record): Builder
    {
        /** @var class-string<Activity> $activityModel */
        $activityModel = config('activitylog.activity_model', Activity::class);
        $subjects = $this->subjects($record);

        return $activityModel::query()
            ->where(function (Builder $query) use ($subjects) {
                foreach ($subjects as $subject) {
                    $query->orWhere(function (Builder $query) use ($subject) {
                        $query
                            ->where('subject_type', $subject->getMorphClass())
                            ->where('subject_id', $subject->getKey());
                    });
                }
            });
    }

    private function castValue(Activity $activity, string $name, mixed $value): mixed
    {
        $class = $activity->subject_type;
        $model = $activity->subject ?? new $class;
        $model->setRawAttributes([$name => $value]);

        return $model->getAttribute($name);
    }

    /**
     * @return list<array{
     *     label: string,
     *     new: array{badge: bool, color: array|string|null, icon: BackedEnum|Htmlable|string|null, text: Htmlable|string},
     *     old: array{badge: bool, color: array|string|null, icon: BackedEnum|Htmlable|string|null, text: Htmlable|string}|null,
     * }>
     */
    private function changes(Activity $activity): array
    {
        $attributes = $activity->attribute_changes?->get('attributes', []) ?? [];
        $old = $activity->attribute_changes?->get('old', []) ?? [];
        $changes = [];

        foreach (array_unique([...array_keys($old), ...array_keys($attributes)]) as $name) {
            if (in_array($name, $this->excludedAttributes, true)) {
                continue;
            }

            $newValue = $attributes[$name] ?? null;
            $oldValue = $old[$name] ?? null;

            if ($this->hideEmptyValues && $this->isEmptyValue($newValue) && $this->isEmptyValue($oldValue)) {
                continue;
            }

            $changes[] = [
                'label' => $this->attributes[$name]->label ?? Str::headline($name),
                'new' => $this->formatValue($activity, $name, $newValue),
                'old' => array_key_exists($name, $old) ? $this->formatValue($activity, $name, $oldValue) : null,
            ];
        }

        return $changes;
    }

    /**
     * @return array{badge: bool, color: array|string|null, icon: BackedEnum|Htmlable|string|null, text: Htmlable|string}
     */
    private function formatValue(Activity $activity, string $name, mixed $value): array
    {
        $formatter = $this->attributes[$name]->formatter ?? null;
        $presentable = $formatter === null ? $this->presentableValue($activity, $name, $value) : null;

        if ($formatter !== null) {
            $value = $this->castValue($activity, $name, $value);
        }

        $label = $presentable instanceof HasLabel ? $presentable->getLabel() : null;
        $text = $formatter !== null
            ? (string) $formatter($value)
            : match (true) {
                $label !== null => $label,
                $value === null => '—',
                is_bool($value) => $value ? 'true' : 'false',
                is_scalar($value) => (string) $value,
                default => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            };

        return match (true) {
            $value === null && $this->nullIcon !== null => [
                'badge' => false,
                'color' => 'gray',
                'icon' => $this->nullIcon,
                'text' => $text,
            ],
            is_bool($value) && $this->boolean => [
                'badge' => false,
                'color' => $value ? $this->trueColor : $this->falseColor,
                'icon' => $value ? $this->trueIcon : $this->falseIcon,
                'text' => $text,
            ],
            $presentable !== null => [
                'badge' => $presentable instanceof HasIcon || $presentable instanceof HasColor,
                'color' => $presentable instanceof HasColor ? $presentable->getColor() : null,
                'icon' => $presentable instanceof HasIcon ? $presentable->getIcon() : null,
                'text' => $text,
            ],
            default => [
                'badge' => false,
                'color' => null,
                'icon' => null,
                'text' => $text,
            ],
        };
    }

    private function isEmptyValue(mixed $value): bool
    {
        return $value === null || $value === [] || is_string($value) && Str::of($value)->trim()->toString() === '';
    }

    /**
     * @return Collection<int, covariant array{
     *     causer: string,
     *     changes: list<array{
     *         label: string,
     *         new: array{badge: bool, color: array|string|null, icon: BackedEnum|Htmlable|string|null, text: Htmlable|string},
     *         old: array{badge: bool, color: array|string|null, icon: BackedEnum|Htmlable|string|null, text: Htmlable|string}|null,
     *     }>,
     *     color: string|null,
     *     date: string,
     *     datetime: string,
     *     description: string,
     *     icon: string,
     *     id: string,
     *     model: string,
     *     revertable: bool,
     *     search: string,
     * }>
     */
    private function items(Model $record): Collection
    {
        return $this
            ->activityQuery($record)
            ->with(['causer', 'subject'])
            ->orderBy('created_at', $this->descending ? 'desc' : 'asc')
            ->get()
            ->toBase()
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
     *     changes: list<array{
     *         label: string,
     *         new: array{badge: bool, color: array|string|null, icon: BackedEnum|Htmlable|string|null, text: Htmlable|string},
     *         old: array{badge: bool, color: array|string|null, icon: BackedEnum|Htmlable|string|null, text: Htmlable|string}|null,
     *     }>,
     *     color: string|null,
     *     date: string,
     *     datetime: string,
     *     description: string,
     *     icon: string,
     *     id: string,
     *     model: string,
     *     revertable: bool,
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
            'id' => (string) $activity->getKey(),
            'model' => $model,
            'revertable' =>
                $this->revertable
                    && $event === 'updated'
                    && $changes !== []
                    && $activity->subject !== null
                    && ($activity->attribute_changes?->get('old', []) ?? []) !== []
                    && Gate::allows('update', $activity->subject),
            'search' => Str::lower(implode(' ', [
                $description,
                $causer,
                $model,
                ...array_map(fn (array $change) => implode(' ', [
                    $change['label'],
                    (string) $change['new']['text'],
                    (string) ($change['old']['text'] ?? ''),
                ]), $changes),
            ])),
        ];
    }

    private function presentableValue(Activity $activity, string $name, mixed $value): HasColor|HasIcon|HasLabel|null
    {
        $candidates = is_array($value) && array_key_exists('id', $value)
            ? [$value, $value['id']]
            : [$value];

        foreach ($candidates as $candidate) {
            try {
                $candidate = $this->castValue($activity, $name, $candidate);
            } catch (TypeError|ValueError) {
                continue;
            }

            if ($candidate instanceof HasColor || $candidate instanceof HasIcon || $candidate instanceof HasLabel) {
                return $candidate;
            }
        }

        return null;
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
            ->unique(fn (Model $subject) => $subject->getMorphClass() . ':' . $subject->getKey())
            ->values();
    }
}
