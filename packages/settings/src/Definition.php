<?php

namespace PHPinnacle\Settings;

use Filament\Navigation\NavigationItem;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use PHPinnacle\Settings\Pages\SettingsPage;

class Definition
{
    public function __construct(
        public string $class,
        public string $label,
        public string $slug,
        public ?string $parent = null,
        public string $icon = 'phosphor-gear',
        public int $sort = 0,
        public bool $dynamic = false,
        public array $schema = [],
    ) {}

    public static function make(string $class): self
    {
        $name = Str::chopEnd(Str::afterLast($class, '\\'), 'Settings');
        $icon = config('phpinnacle-settings.navigation.icon');

        return new self($class, Str::headline($name), Str::slug($name), null, $icon, 0, method_exists($class, 'form'));
    }

    public function enabled(): bool
    {
        return (
            Gate::allows('manage', $this->class) || Gate::allows(sprintf('manage_%s_settings', Str::snake($this->slug)))
        );
    }

    public function form(Schema $schema): Schema
    {
        if ($this->dynamic) {
            call_user_func([$this->class, 'form'], $schema);
        } else {
            $schema->components($this->schema);
        }

        return $schema;
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function navigation(): NavigationItem
    {
        return NavigationItem::make()
            ->label($this->label)
            ->icon($this->icon)
            ->visible($this->enabled(...))
            ->isActiveWhen(fn () => request()->route('group') === $this->slug)
            ->url(fn () => SettingsPage::getUrl([
                'group' => $this->slug,
            ]));
    }

    public function parent(string $class): self
    {
        $this->parent = $class;

        return $this;
    }

    public function schema(array $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    public function slug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function sort(int $sort): self
    {
        $this->sort = $sort;

        return $this;
    }
}
