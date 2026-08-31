<?php

namespace PHPinnacle\Tags\Filters;

use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use PHPinnacle\Tags\Models\Tag;

class TagsFilter extends Filter
{
    private ?string $model = null;

    public static function getDefaultName(): ?string
    {
        return 'tags';
    }

    public function model(string $class): self
    {
        $this->model = $class;

        return $this;
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-tags::fields.filter'))
            ->visible(fn () => Gate::allows('view', Tag::class))
            ->schema([
                CheckboxList::make('tags')
                    ->columns(4)
                    ->bulkToggleable()
                    ->searchable()
                    ->options(fn () => Tag::select($this->model)),
            ])
            ->query(function (Builder $query, array $data) {
                $tags = array_filter($data['tags'] ?? []);

                return !empty($tags)
                    ? $query->whereHas('tags', fn (Builder $query) => $query->whereKey($tags))
                    : $query;
            })
            ->indicateUsing(function (array $data) {
                $tags = $data['tags'] ?? [];

                if (empty($tags)) {
                    return [];
                }

                return Tag::list(...$tags)
                    ->map(fn (string $tag) => __('phpinnacle-tags::filters.indicator', ['tag' => $tag]))
                    ->all();
            });
    }
}
