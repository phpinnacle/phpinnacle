<?php

namespace PHPinnacle\Recens;

use Closure;
use Filament\Pages\Page;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Icons\Heroicon;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Livewire;
use PHPinnacle\Recens\Models\Recent;

#[Singleton]
class Recorder
{
    use EvaluatesClosures;

    private array $pages = [];

    public function record(Page $page): void
    {
        if (Livewire::isLivewireRequest()) {
            return;
        }

        $default = $this->defaults($page);
        $custom = $this->evaluate($this->pages[get_class($page)] ?? [], [
            'page' => $page,
        ]);

        Recent::record(is_array($custom) ? array_replace($default, $custom) : $default);
    }

    public function register(string $page, ?Closure $callback = null): void
    {
        $this->pages[$page] = $callback;
    }

    private function defaults(Page $page): array
    {
        $title = $page->getTitle();
        $title = $title instanceof Htmlable ? strip_tags($title->toHtml()) : $title;

        $group = $page::getNavigationGroup();

        $icon = $page::getActiveNavigationIcon();
        $icon = $icon instanceof Heroicon ? sprintf('heroicon-%s', $icon->value) : $icon;

        return [
            'title' => $title,
            'group' => $group,
            'icon' => $icon,
        ];
    }
}
