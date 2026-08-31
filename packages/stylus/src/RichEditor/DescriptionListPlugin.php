<?php

namespace PHPinnacle\Stylus\RichEditor;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Support\Facades\FilamentAsset;
use PHPinnacle\Stylus\StylusServiceProvider;
use PHPinnacle\Stylus\TipTap;
use Tiptap\Core\Extension;

class DescriptionListPlugin implements RichContentPlugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * @return array<Action>
     */
    public function getEditorActions(): array
    {
        return [];
    }

    /**
     * @return array<RichEditorTool>
     */
    public function getEditorTools(): array
    {
        return [
            RichEditorTool::make('descriptionList')
                ->label(__('phpinnacle-stylus::forms.description_list.label'))
                ->icon('heroicon-o-queue-list')
                ->jsHandler('getEditor().chain().focus().insertDescriptionList().run()'),
            RichEditorTool::make('addDescriptionPair')
                ->label(__('phpinnacle-stylus::forms.description_list.actions.add_pair'))
                ->icon('heroicon-o-plus')
                ->jsHandler('getEditor().chain().focus().addDescriptionPair().run()'),
            RichEditorTool::make('removeDescriptionPair')
                ->label(__('phpinnacle-stylus::forms.description_list.actions.remove_pair'))
                ->icon('heroicon-o-trash')
                ->jsHandler('getEditor().chain().focus().removeDescriptionPair().run()'),
        ];
    }

    /**
     * @return array<string>
     */
    public function getTipTapJsExtensions(): array
    {
        return [
            FilamentAsset::getScriptSrc('description-list', StylusServiceProvider::PACKAGE),
        ];
    }

    /**
     * @return array<Extension>
     */
    public function getTipTapPhpExtensions(): array
    {
        return [
            new TipTap\DescriptionListExtension,
            new TipTap\DescriptionTermExtension,
            new TipTap\DescriptionDetailsExtension,
        ];
    }
}
