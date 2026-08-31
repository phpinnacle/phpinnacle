<?php

namespace PHPinnacle\Stylus\RichEditor;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentAsset;
use PHPinnacle\Stylus\StylusServiceProvider;
use PHPinnacle\Stylus\TipTap;
use Tiptap\Core\Extension;

class ConditionPlugin implements RichContentPlugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    /** @return array<Action> */
    public function getEditorActions(): array
    {
        return [
            $this->conditionAction(
                name: 'blockCondition',
                heading: __('phpinnacle-stylus::forms.condition.block.modal_heading'),
                setCommand: 'setBlockCondition',
                unsetCommand: 'unsetBlockCondition',
            ),
            $this->conditionAction(
                name: 'inlineCondition',
                heading: __('phpinnacle-stylus::forms.condition.inline.modal_heading'),
                setCommand: 'setInlineCondition',
                unsetCommand: 'unsetInlineCondition',
            ),
        ];
    }

    /** @return array<RichEditorTool> */
    public function getEditorTools(): array
    {
        return [
            RichEditorTool::make('blockCondition')
                ->label(__('phpinnacle-stylus::forms.condition.block.label'))
                ->icon('heroicon-o-rectangle-group')
                ->activeKey('conditionBlock')
                ->action(
                    arguments: '{ condition: $getEditor()?.getAttributes(\'conditionBlock\')?.condition ?? null }',
                ),
            RichEditorTool::make('inlineCondition')
                ->label(__('phpinnacle-stylus::forms.condition.inline.label'))
                ->icon('heroicon-o-variable')
                ->activeKey('conditionInline')
                ->action(
                    arguments: '{ condition: $getEditor()?.getAttributes(\'conditionInline\')?.condition ?? null }',
                ),
        ];
    }

    /** @return array<string> */
    public function getTipTapJsExtensions(): array
    {
        return [
            FilamentAsset::getScriptSrc('condition', StylusServiceProvider::PACKAGE),
        ];
    }

    /** @return array<Extension> */
    public function getTipTapPhpExtensions(): array
    {
        return [
            new TipTap\ConditionBlockExtension,
            new TipTap\ConditionInlineExtension,
        ];
    }

    private function conditionAction(
        string $name,
        string $heading,
        string $setCommand,
        string $unsetCommand,
    ): Action {
        return Action::make($name)
            ->modalHeading($heading)
            ->modalSubmitActionLabel(__('phpinnacle-stylus::forms.condition.actions.apply'))
            ->modalWidth(Width::Large)
            ->fillForm(fn (array $arguments) => [
                'condition' => $arguments['condition'] ?? null,
            ])
            ->schema([
                TextInput::make('condition')
                    ->label(__('phpinnacle-stylus::forms.condition.fields.condition.label'))
                    ->helperText(__('phpinnacle-stylus::forms.condition.fields.condition.helper'))
                    ->rules(['nullable', 'string'])
                    ->maxLength(255),
            ])
            ->action(function (array $arguments, array $data, RichEditor $component) use ($setCommand, $unsetCommand) {
                $condition = $data['condition'] ?? null;
                $condition = is_string($condition) ? trim($condition) : null;
                $command = filled($condition)
                    ? EditorCommand::make($setCommand, arguments: [$condition])
                    : EditorCommand::make($unsetCommand);

                $component->runCommands(
                    [$command],
                    editorSelection: $arguments['editorSelection'],
                );
            });
    }
}
