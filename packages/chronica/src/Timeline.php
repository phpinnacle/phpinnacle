<?php

namespace PHPinnacle\Chronica;

use Filament\Support\Icons\Heroicon;
use PHPinnacle\Chronica\Timeline\Attribute;
use PHPinnacle\Chronica\Timeline\Relation;
use RalphJSmit\Filament\Activitylog\Filament\Infolists\Components\Timeline as Component;

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

    public function apply(Component $component): Component
    {
        $attributeValues = $attributeLabels = [];
        $relations = $relationsTitles = $relationsTitlesUsing = [];
        $modelsLabels = [
            $this->class => $this->label,
        ];

        foreach ($this->attributes as $name => $attribute) {
            if ($attribute->formatter !== null) {
                $attributeValues[$name] = $attribute->formatter;
            }

            if ($attribute->label !== null) {
                $attributeLabels[$name] = $attribute->label;
            }
        }

        foreach ($this->relations as $relation) {
            $relations[] = $relation->name;

            if ($relation->label !== null) {
                $modelsLabels[$relation->class] = $relation->label;
            }

            if ($relation->title !== null) {
                if (is_string($relation->title)) {
                    $relationsTitles[$relation->class] = $relation->title;
                } else {
                    $relationsTitlesUsing[$relation->class] = $relation->title;
                }
            }
        }

        return $component
            ->hiddenLabel()
            ->emptyStateHeading(__('phpinnacle-chronica::messages.empty.heading'))
            ->emptyStateDescription(__('phpinnacle-chronica::messages.empty.description'))
            ->emptyStateIcon(config('phpinnacle-chronica.icon', Heroicon::OutlinedArrowsUpDown))
            ->causerName(null, __('phpinnacle-chronica::messages.causer.unknown'))
            ->attributeValues($attributeValues)
            ->attributeLabels($attributeLabels)
            ->withRelations(array_unique($relations))
            ->modelLabels($modelsLabels)
            ->recordTitleAttributes($relationsTitles)
            ->getRecordTitlesUsing($relationsTitlesUsing)
            ->itemIcons($this->icons)
            ->itemIconColors($this->colorize ? $this->iconColors : [])
            ->changesSummaryOldAttributeValues($this->verbose)
            ->sortActivitiesDescending($this->descending)
            ->searchable($this->searchable)
            ->compact($this->compact);
    }

    public function attribute(string $name, \Closure $formatter): self
    {
        $this->attributes[$name] = $formatter;

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
}
