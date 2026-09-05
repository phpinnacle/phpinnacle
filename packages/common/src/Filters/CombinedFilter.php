<?php

namespace PHPinnacle\Common\Filters;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use PHPinnacle\Common\Filters\CombinedFilter\Filter;

class CombinedFilter extends BaseFilter
{
    /**
     * @var array<Filter>
     */
    private array $filters = [];

    /**
     * @var array<string, list<string>>
     */
    private array $dependencies = [];

    /**
     * @var list<Group>|null
     */
    private ?array $cachedForm = null;

    public static function getDefaultName(): string
    {
        return 'combined';
    }

    public function filters(Filter ...$filters): static
    {
        $this->filters = $filters;
        $this->dependencies = [];

        foreach ($this->filters as $filter) {
            foreach ($filter->depends as $key) {
                $this->dependencies[$key][] = $filter->name;
            }
        }

        return $this;
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->schema($this->buildForm(...))
            ->query(function (Builder $query, array $data) {
                foreach ($this->filters as $filter) {
                    $filter->apply($query, $data);
                }

                return $query;
            })
            ->indicateUsing(function (array $data) {
                $components = $this->getTable()->getFiltersForm()->getComponent($this->getName())->getChildComponents();
                $components = ($components[0] ?? null)?->getChildComponents() ?? [];

                $indicators = [];
                $options = [];

                /** @var Select $component */
                foreach ($components as $component) {
                    $options[$component->getName()] = $component->getOptions();
                }

                foreach ($this->filters as $filter) {
                    $indicator = $filter->indicator($data, $options);

                    if ($indicator !== null) {
                        $indicators[] = $indicator;
                    }
                }

                return $indicators;
            });
    }

    /**
     * @return list<Group>
     */
    private function buildForm(): array
    {
        return $this->cachedForm ??= [
            Group::make()
                ->key($this->getName())
                ->columns($this->getColumns())
                ->columnSpan($this->getColumnSpan())
                ->schema(array_map(fn (Filter $filter) => $filter->select($this->dependencies), $this->filters)),
        ];
    }
}
