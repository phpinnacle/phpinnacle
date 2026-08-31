<?php

namespace PHPinnacle\Common\Actions;

use Livewire\Component;

class Refresh
{
    private const string EVENT_PAGE = 'refresh-page';

    private const string EVENT_SIDEBAR = 'refresh-sidebar';

    private const string EVENT_TOPBAR = 'refresh-topbar';

    public static function all(Component $livewire): void
    {
        self::page($livewire);
        self::sidebar($livewire);
        self::topbar($livewire);
    }

    public static function nav(Component $livewire): void
    {
        self::sidebar($livewire);
        self::topbar($livewire);
    }

    public static function page(Component $livewire): void
    {
        $livewire->dispatch(self::EVENT_PAGE);
    }

    public static function sidebar(Component $livewire): void
    {
        $livewire->dispatch(self::EVENT_SIDEBAR);
    }

    public static function topbar(Component $livewire): void
    {
        $livewire->dispatch(self::EVENT_TOPBAR);
    }
}
