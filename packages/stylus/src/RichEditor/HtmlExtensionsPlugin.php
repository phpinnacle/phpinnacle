<?php

namespace PHPinnacle\Stylus\RichEditor;

use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Support\Facades\FilamentAsset;
use PHPinnacle\Stylus\StylusServiceProvider;
use PHPinnacle\Stylus\TipTap;

class HtmlExtensionsPlugin implements RichContentPlugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getEditorActions(): array
    {
        return [];
    }

    public function getEditorTools(): array
    {
        return [];
    }

    public function getTipTapJsExtensions(): array
    {
        return [
            FilamentAsset::getScriptSrc('html-extensions', StylusServiceProvider::PACKAGE),
        ];
    }

    public function getTipTapPhpExtensions(): array
    {
        return [
            new TipTap\DivExtension,
            new TipTap\SectionExtension,
            new TipTap\ArticleExtension,
        ];
    }
}
