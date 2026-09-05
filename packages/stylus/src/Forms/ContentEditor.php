<?php

namespace PHPinnacle\Stylus\Forms;

use Filament\Actions\Action;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use PHPinnacle\Stylus\Support\HtmlFormatter;

class ContentEditor extends Group
{
    protected ?string $htmlOnlyField = null;

    protected ?string $htmlOnlyValue = null;

    public static function onHtmlOnlyFieldChanged(string $htmlValue): \Closure
    {
        return function (Get $get, Set $set, $state) use ($htmlValue) {
            if (($state instanceof \BackedEnum ? $state->value : $state) === $htmlValue) {
                $set('content_code', HtmlFormatter::format($get('content') ?? ''));
                $set('content_mode', 'code');
            }
        };
    }

    public function htmlOnly(string $field, string $value): static
    {
        $this->htmlOnlyField = $field;
        $this->htmlOnlyValue = $value;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $self = $this;
        $isHtmlOnly = function (Get $get) use ($self) {
            if ($self->htmlOnlyField === null) {
                return false;
            }
            $value = $get($self->htmlOnlyField);

            return ($value instanceof \BackedEnum ? $value->value : $value) === $self->htmlOnlyValue;
        };

        $this->schema([
            Hidden::make('content_mode')
                ->default('visual')
                ->dehydrated(false),
            RichEditor::make('content')
                ->visible(fn (Get $get) => $get('content_mode') !== 'code' && !$isHtmlOnly($get))
                ->dehydrated(fn (Get $get) => $get('content_mode') !== 'code' && !$isHtmlOnly($get))
                ->hintAction(
                    Action::make('switch_to_code')
                        ->label(__('phpinnacle-stylus::forms.content_editor.actions.switch_to_code'))
                        ->icon('phosphor-code')
                        ->color('gray')
                        ->visible(fn (Get $get) => !$isHtmlOnly($get))
                        ->action(function (Get $get, Set $set) {
                            $set('content_code', HtmlFormatter::format($get('content')));
                            $set('content_mode', 'code');
                        }),
                ),
            CodeEditor::make('content_code')
                ->visible(fn (Get $get) => $get('content_mode') === 'code' || $isHtmlOnly($get))
                ->dehydrated(false)
                ->language(Language::Html)
                ->hintAction(
                    Action::make('switch_to_visual')
                        ->label(__('phpinnacle-stylus::forms.content_editor.actions.switch_to_visual'))
                        ->icon('phosphor-eye')
                        ->color('gray')
                        ->visible(fn (Get $get) => !$isHtmlOnly($get))
                        ->action(function (Get $get, Set $set) {
                            $set('content', $get('content_code'));
                            $set('content_mode', 'visual');
                        }),
                ),
        ]);
    }
}
