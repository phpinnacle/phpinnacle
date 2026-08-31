<?php

namespace PHPinnacle\Razor\Forms;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;

class Editor extends RichEditor
{
    public function setUp(): void
    {
        parent::setUp();

        $this
            ->resizableImages()
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                [ToolbarButtonGroup::make('Paragraph', ['paragraph', 'h1', 'h2', 'h3'])->textualButtons()],
                [ToolbarButtonGroup::make('Alignment', ['alignStart', 'alignCenter', 'alignJustify', 'alignEnd'])],
                ['highlight', 'textColor'],
                ['horizontalRule', 'blockquote', 'codeBlock', 'details', 'bulletList', 'orderedList'],
                ['grid', 'table', 'attachFiles'],
                ['undo', 'redo', 'clearFormatting'],
            ])
            ->extraInputAttributes([
                'class' => 'fi-fo-rich-editor-large',
            ], merge: true);
    }
}
