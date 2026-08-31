<?php

namespace PHPinnacle\Stylus\Forms;

use Filament\Forms\Components\RichEditor as BaseRichEditor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use PHPinnacle\Stylus\RichEditor\ConditionPlugin;
use PHPinnacle\Stylus\RichEditor\DescriptionListPlugin;
use PHPinnacle\Stylus\RichEditor\HtmlExtensionsPlugin;

class RichEditor extends BaseRichEditor
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->resizableImages()
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'link'],
                [ToolbarButtonGroup::make('Paragraph', ['paragraph', 'h1', 'h2', 'h3'])],
                [ToolbarButtonGroup::make('Alignment', ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'])],
                ['bulletList', 'orderedList', 'descriptionList', 'blockquote', 'codeBlock'],
                ['grid', 'gridDelete'],
                ['table', 'attachFiles'],
                ['mergeTags'],
                [ToolbarButtonGroup::make(
                    __('phpinnacle-stylus::forms.condition.label'),
                    ['blockCondition', 'inlineCondition'],
                )],
                ['clearFormatting', 'undo', 'redo'],
            ])
            ->floatingToolbars([
                'descriptionList' => [
                    'addDescriptionPair',
                    'removeDescriptionPair',
                ],
                'table' => [
                    'tableAddColumnBefore',
                    'tableAddColumnAfter',
                    'tableDeleteColumn',
                    'tableAddRowBefore',
                    'tableAddRowAfter',
                    'tableDeleteRow',
                    'tableMergeCells',
                    'tableSplitCell',
                    'tableToggleHeaderRow',
                    'tableToggleHeaderCell',
                    'tableDelete',
                ],
            ])
            ->plugins([
                ConditionPlugin::make(),
                DescriptionListPlugin::make(),
                HtmlExtensionsPlugin::make(),
            ])
            ->extraInputAttributes([
                'class' => 'fi-fo-rich-editor-extra-extra-large',
            ], merge: true);
    }
}
