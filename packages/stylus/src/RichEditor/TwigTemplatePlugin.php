<?php

namespace PHPinnacle\Stylus\RichEditor;

use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\View\Components\IconComponent;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PHPinnacle\Stylus\Contracts\Condition;
use PHPinnacle\Stylus\Contracts\Filter;
use PHPinnacle\Stylus\Contracts\Variable;
use PHPinnacle\Stylus\Enums\ConditionKind;
use PHPinnacle\Stylus\Enums\FilterOutput;
use PHPinnacle\Stylus\Forms\TwigEditor;
use PHPinnacle\Stylus\StylusServiceProvider;
use PHPinnacle\Stylus\TipTap;
use Tiptap\Core\Extension;

use function Filament\Support\generate_icon_html;

class TwigTemplatePlugin implements RichContentPlugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    /** @return array<Action> */
    public function getEditorActions(): array
    {
        return [
            $this->configureVariableFilterAction(),
            $this->configureConditionOperandFilterAction(),
            $this->configureLoopFilterAction(),
            $this->configureApplyFilterAction(),
            $this->insertApplyFilterAction(),
            $this->insertConditionRuleAction('insertTwigIf'),
            $this->insertConditionRuleAction('insertTwigTableRowIf', tableRow: true),
            $this->loopAction('insertTwigFor', 'insertTwigFor'),
            $this->insertTableLoopAction(
                'configureTwigTableRowLoop',
                'setTwigTableRowLoop',
            ),
            $this->insertTableLoopAction(
                'configureTwigTableCellLoop',
                'setTwigTableCellLoop',
            ),
        ];
    }

    /** @return array<RichEditorTool> */
    public function getEditorTools(): array
    {
        return [
            RichEditorTool::make('twigVariables')
                ->label(__('phpinnacle-stylus::forms.twig_editor.tools.variables'))
                ->icon('heroicon-o-variable')
                ->extraAttributes(['data-stylus-twig-panel-trigger' => 'variables'])
                ->jsHandler("twigTogglePanel('variables')")
                ->activeJsExpression('twigVariablePanelOpen'),
            RichEditorTool::make('twigOutline')
                ->label(__('phpinnacle-stylus::forms.twig_editor.tools.outline'))
                ->icon('heroicon-o-list-bullet')
                ->extraAttributes(['data-stylus-twig-panel-trigger' => 'outline'])
                ->jsHandler("twigTogglePanel('outline')")
                ->activeJsExpression('twigOutlinePanelOpen'),
            RichEditorTool::make('twigTemplateStructure')
                ->label(__('phpinnacle-stylus::forms.twig_editor.tools.structure'))
                ->icon('heroicon-o-eye')
                ->jsHandler(
                    'twigTemplateStructureVisible = ! twigTemplateStructureVisible; twigStoreVariablePreferences()',
                )
                ->activeJsExpression('twigTemplateStructureVisible'),
            RichEditorTool::make('twigConditionViewBoth')
                ->label(__('phpinnacle-stylus::forms.twig_editor.tools.condition_view_both'))
                ->icon('heroicon-o-eye')
                ->jsHandler("twigSetConditionBranchMode('both')")
                ->activeJsExpression("twigConditionBranchMode === 'both'"),
            RichEditorTool::make('twigConditionViewIf')
                ->label(__('phpinnacle-stylus::forms.twig_editor.tools.condition_view_if'))
                ->icon('heroicon-o-chevron-up')
                ->jsHandler("twigSetConditionBranchMode('if')")
                ->activeJsExpression("twigConditionBranchMode === 'if'"),
            RichEditorTool::make('twigConditionViewElse')
                ->label(__('phpinnacle-stylus::forms.twig_editor.tools.condition_view_else'))
                ->icon('heroicon-o-chevron-down')
                ->jsHandler("twigSetConditionBranchMode('else')")
                ->activeJsExpression("twigConditionBranchMode === 'else'"),
            RichEditorTool::make('insertTwigApply')
                ->label(__('phpinnacle-stylus::forms.twig_editor.tools.insert_apply'))
                ->icon('heroicon-o-sparkles')
                ->extraAttributes(['data-stylus-twig-panel-trigger' => 'apply'])
                ->jsHandler('twigOpenApplyInsertPanel($getEditor()?.state.selection.toJSON() ?? null)')
                ->activeJsExpression("twigVariableFilterPanelOpen && twigFilterPanelTarget === 'apply-insert'"),
            RichEditorTool::make('insertTwigSnippet')
                ->label(__('phpinnacle-stylus::forms.twig_editor.tools.insert_snippet'))
                ->icon('heroicon-o-squares-plus')
                ->extraAttributes(['data-stylus-twig-panel-trigger' => 'snippets'])
                ->jsHandler("twigTogglePanel('snippets')")
                ->activeJsExpression('twigSnippetPanelOpen'),
            RichEditorTool::make('unwrapTwigIf')
                ->label(__('phpinnacle-stylus::forms.twig_editor.tools.unwrap_condition'))
                ->icon('heroicon-o-arrow-up-on-square')
                ->activeKey('twigIf')
                ->jsHandler('$getEditor()?.chain().focus().unwrapTwigIf().run()'),
            RichEditorTool::make('deleteTwigIf')
                ->label(__('phpinnacle-stylus::forms.twig_editor.tools.delete_condition'))
                ->icon('heroicon-o-trash')
                ->activeKey('twigIf')
                ->jsHandler('$getEditor()?.chain().focus().deleteTwigIf().run()'),
            RichEditorTool::make('unwrapTwigInlineIf')
                ->label(__('phpinnacle-stylus::forms.twig_editor.tools.unwrap_inline_condition'))
                ->icon('heroicon-o-arrow-up-on-square')
                ->activeKey('twigInlineIf')
                ->jsHandler('$getEditor()?.chain().focus().unwrapTwigInlineIf().run()'),
            RichEditorTool::make('deleteTwigInlineIf')
                ->label(__('phpinnacle-stylus::forms.twig_editor.tools.delete_inline_condition'))
                ->icon('heroicon-o-trash')
                ->activeKey('twigInlineIf')
                ->jsHandler('$getEditor()?.chain().focus().deleteTwigInlineIf().run()'),
            RichEditorTool::make('insertTwigFor')
                ->label(__('phpinnacle-stylus::forms.twig_editor.tools.insert_loop'))
                ->icon('heroicon-o-arrow-path')
                ->action(arguments: <<<'JS'
                    {
                        loopStack: window.PHPinnacleStylusTwigEditor?.getLoopStack($getEditor()) ?? [],
                    }
                    JS),
            RichEditorTool::make('configureTwigTableRowLoop')
                ->label(__('phpinnacle-stylus::forms.twig_editor.tools.repeat_row'))
                ->icon('heroicon-o-arrows-up-down')
                ->extraAttributes(['data-stylus-twig-panel-trigger' => 'row-loop'])
                ->jsHandler(
                    'twigOpenTableLoopPanel(window.PHPinnacleStylusTwigEditor?.getTableRowLoopArguments($getEditor()) ?? null)',
                )
                ->activeJsExpression(
                    '(window.PHPinnacleStylusTwigEditor?.canConfigureTwigTableRowLoop($getEditor()) || window.PHPinnacleStylusTwigEditor?.hasTwigTableRowLoop($getEditor())) ?? false',
                )
                ->disabledWhenNotActive()
                ->activeStyling(false),
            RichEditorTool::make('configureTwigTableCellLoop')
                ->label(__('phpinnacle-stylus::forms.twig_editor.tools.repeat_cells'))
                ->icon('heroicon-o-arrows-right-left')
                ->extraAttributes(['data-stylus-twig-panel-trigger' => 'cell-loop'])
                ->jsHandler(
                    'twigOpenTableLoopPanel(window.PHPinnacleStylusTwigEditor?.getTableCellLoopArguments($getEditor()) ?? null)',
                )
                ->activeJsExpression(
                    '(window.PHPinnacleStylusTwigEditor?.canConfigureTwigTableCellLoop($getEditor()) || window.PHPinnacleStylusTwigEditor?.hasTwigTableCellLoop($getEditor())) ?? false',
                )
                ->disabledWhenNotActive()
                ->activeStyling(false),
        ];
    }

    /** @return array<string> */
    public function getTipTapJsExtensions(): array
    {
        return [
            FilamentAsset::getScriptSrc('twig-rich-editor', StylusServiceProvider::PACKAGE),
        ];
    }

    /** @return array<Extension> */
    public function getTipTapPhpExtensions(): array
    {
        return [
            new TipTap\TwigVariableExtension,
            new TipTap\TwigInlineIfExtension,
            new TipTap\TwigApplyExtension,
            new TipTap\TwigThenExtension,
            new TipTap\TwigElseExtension,
            new TipTap\TwigIfExtension,
            new TipTap\TwigForBodyExtension,
            new TipTap\TwigForElseExtension,
            new TipTap\TwigForExtension,
            new TipTap\TwigTableLoopExtension,
            new TipTap\TwigTableConditionExtension,
        ];
    }

    /** @return list<Builder> */
    private function collectionFilterFields(TwigEditor $component): array
    {
        $blocks = [];

        foreach ($component->getCollectionFilters() as $filter) {
            $blocks[] = $this->filterBlock($filter);
        }

        return (
            $blocks === []
                ? []
                : [
                    Builder::make('filters')
                        ->label(__('phpinnacle-stylus::forms.twig_editor.fields.filters'))
                        ->blocks($blocks)
                        ->blockNumbers(false)
                        ->blockIcons()
                        ->addActionLabel(__('phpinnacle-stylus::forms.twig_editor.actions.add_filter'))
                        ->collapsed(),
                ]
        );
    }

    /** @param array<string, mixed> $data */
    private function conditionFormClauseToAst(Condition $condition, array $data): array
    {
        $expression = [
            'type' => $condition->getType()->value,
            'negated' => ($data['negated'] ?? false) === true,
        ];

        if ($condition->getType() === ConditionKind::Comparison) {
            $expression['left'] = $this->conditionFormOperand('left', $data);
            $expression['operator'] = $condition->getName();
            $expression['right'] = $this->conditionFormOperand('right', $data);

            return $expression;
        }

        $expression['subject'] = $this->conditionFormOperand('subject', $data);

        if ($condition->getType() === ConditionKind::Test) {
            $expression['test'] = $condition->getName();
        }

        return $expression;
    }

    /** @param array<string, mixed> $data */
    private function conditionFormOperand(string $prefix, array $data): array
    {
        $type = $data["{$prefix}_type"] ?? 'variable';

        if ($type === 'variable') {
            return ['type' => 'variable', 'name' => $data["{$prefix}_variable"] ?? null];
        }

        return [
            'type' => 'literal',
            'valueType' => $type,
            'value' => $data["{$prefix}_{$type}"] ?? null,
        ];
    }

    private function conditionLabel(string $conditionType, TwigEditor $component): string
    {
        return (string) $component->getCondition($conditionType)?->getLabel();
    }

    private function conditionLiteralType(string $variableType): ?string
    {
        return match ($variableType) {
            'text', 'date' => 'string',
            'number', 'integer' => 'number',
            'boolean' => 'boolean',
            default => null,
        };
    }

    /** @param array<string, mixed> $arguments */
    private function conditionOperandFromArguments(array $arguments): array
    {
        $conditionAst = is_array($arguments['conditionAst'] ?? null)
            ? $arguments['conditionAst']
            : [];
        $children = is_array($conditionAst['children'] ?? null)
            ? array_values($conditionAst['children'])
            : [];
        $conditionIndex = $arguments['conditionIndex'] ?? null;
        $operandKey = $arguments['operandKey'] ?? null;
        $clause = is_int($conditionIndex) && is_array($children[$conditionIndex] ?? null)
            ? $children[$conditionIndex]
            : [];

        return (
            is_string($operandKey) && is_array($clause[$operandKey] ?? null)
                ? $clause[$operandKey]
                : []
        );
    }

    /**
     * @param  array<int, mixed>  $loopStack
     * @return list<Component>
     */
    private function conditionOperandSchema(
        string $side,
        TwigEditor $component,
        array $loopStack,
        Condition $condition,
    ): array {
        $literals = $side === 'right';
        $types = $condition->getTypes();
        $matchVariable = $literals && $condition->matchesVariableTypes() ? 'left_variable' : null;
        $resetVariable = $side === 'left' && $condition->matchesVariableTypes() ? 'right_variable' : null;
        $literalVariable = $literals ? 'left_variable' : null;
        $resetType = $side === 'left' ? 'right_type' : null;
        $scope = $component->getVariableScope($loopStack);
        $variableField = Select::make("{$side}_variable")
            ->label(__('phpinnacle-stylus::forms.twig_editor.fields.variable'))
            ->options(function (Get $get) use ($matchVariable, $scope, $types) {
                if ($matchVariable === null) {
                    return $scope->getVariableOptions($types);
                }

                $matchingVariableName = $get($matchVariable);
                $matchingVariable = is_string($matchingVariableName)
                    ? $scope->getVariable($matchingVariableName)
                    : null;

                if (!$matchingVariable || $types !== [] && !in_array($matchingVariable->getType(), $types, true)) {
                    return [];
                }

                return $scope->getVariableOptions([$matchingVariable->getType()]);
            })
            ->native()
            ->required()
            ->visible(fn (Get $get) => !$literals || $get("{$side}_type") === 'variable');

        if ($resetVariable !== null || $resetType !== null) {
            $variableField
                ->live()
                ->afterStateUpdated(function (Set $set) use ($resetType, $resetVariable) {
                    if ($resetVariable !== null) {
                        $set($resetVariable, null);
                    }

                    if ($resetType !== null) {
                        $set($resetType, 'variable');
                    }
                });
        }

        $fields = [
            ...(
                $literals
                    ? [
                        Select::make("{$side}_type")
                            ->label(__('phpinnacle-stylus::forms.twig_editor.fields.operand_type'))
                            ->options(function (Get $get) use ($literalVariable, $scope) {
                                $options = [
                                    'variable' => __('phpinnacle-stylus::forms.twig_editor.condition.variable'),
                                ];
                                $variableName = $literalVariable !== null ? $get($literalVariable) : null;
                                $variable = is_string($variableName) ? $scope->getVariable($variableName) : null;
                                $literalType = $variable ? $this->conditionLiteralType($variable->getType()) : null;

                                if ($literalType !== null) {
                                    $options[$literalType] = __('phpinnacle-stylus::forms.twig_editor.condition.'
                                    . $literalType);
                                }

                                return $options;
                            })
                            ->default('variable')
                            ->native()
                            ->live()
                            ->required(),
                    ] : []
            ),
            $variableField,
        ];

        if ($literals) {
            $fields[] = TextInput::make("{$side}_string")
                ->label(__('phpinnacle-stylus::forms.twig_editor.fields.value'))
                ->required()
                ->maxLength(500)
                ->visible(fn (Get $get) => $get("{$side}_type") === 'string');
            $fields[] = TextInput::make("{$side}_number")
                ->label(__('phpinnacle-stylus::forms.twig_editor.fields.value'))
                ->required()
                ->numeric()
                ->visible(fn (Get $get) => $get("{$side}_type") === 'number');
            $fields[] = Select::make("{$side}_boolean")
                ->label(__('phpinnacle-stylus::forms.twig_editor.fields.value'))
                ->boolean(
                    trueLabel: __('phpinnacle-stylus::forms.twig_editor.condition.true'),
                    falseLabel: __('phpinnacle-stylus::forms.twig_editor.condition.false'),
                )
                ->default(false)
                ->selectablePlaceholder(false)
                ->required()
                ->visible(fn (Get $get) => $get("{$side}_type") === 'boolean');
        }

        return (
            $literals
                ? [Group::make($fields)->columns(2)]
                : $fields
        );
    }

    /**
     * @param  array<int, mixed>  $loopStack
     * @return list<Component>
     */
    private function conditionRuleSchema(
        string $conditionType,
        TwigEditor $component,
        array $loopStack,
    ): array {
        $condition = $component->getCondition($conditionType);

        if (!$condition) {
            return [];
        }

        if ($condition->getType() === ConditionKind::Comparison) {
            return [
                ...$this->conditionOperandSchema(
                    'left',
                    $component,
                    $loopStack,
                    $condition,
                ),
                ...$this->conditionOperandSchema(
                    'right',
                    $component,
                    $loopStack,
                    $condition,
                ),
            ];
        }

        return [
            Group::make($this->conditionOperandSchema(
                'subject',
                $component,
                $loopStack,
                $condition,
            ))->columns(1),
        ];
    }

    private function conditionValidationField(Condition $condition): string
    {
        return $condition->getType() === ConditionKind::Comparison
            ? 'left_variable'
            : 'subject_variable';
    }

    private function configureApplyFilterAction(): Action
    {
        return $this
            ->configureFilterModal(Action::make('configureTwigApplyFilter'))
            ->modalHeading(function (array $arguments, TwigEditor $component) {
                $filter = $this->getFilterFromArguments($arguments, $component);

                return __('phpinnacle-stylus::forms.twig_editor.modals.filter', [
                    'filter' => $filter?->getLabel() ?? '',
                ]);
            })
            ->modalSubmitActionLabel(__('phpinnacle-stylus::forms.twig_editor.actions.apply'))
            ->modalWidth(Width::Large)
            ->fillForm(function (array $arguments) {
                $filterIndex = $arguments['filterIndex'] ?? null;
                $filters = is_array($arguments['filters'] ?? null) ? $arguments['filters'] : [];
                $filter = is_int($filterIndex) && is_array($filters[$filterIndex] ?? null)
                    ? $filters[$filterIndex]
                    : [];

                return (
                    is_array($filter['configuration'] ?? null)
                        ? $filter['configuration']
                        : []
                );
            })
            ->schema(function (array $arguments, TwigEditor $component) {
                $filterName = $arguments['filterName'] ?? null;

                return (
                    is_string($filterName)
                        ? $component->getFilterSchema($filterName)
                        : []
                );
            })
            ->action(function (array $arguments, array $data, TwigEditor $component) {
                $filterName = $arguments['filterName'] ?? null;
                $filter = is_string($filterName) ? $component->getFilter($filterName) : null;

                if (!$filter?->supportsBlocks() || $filter->getSchema() === []) {
                    throw ValidationException::withMessages([
                        'filters' => __('phpinnacle-stylus::forms.twig_editor.validation.block_filter_unavailable'),
                    ]);
                }

                $configuredFilters = $this->filtersToFormState($arguments['filters'] ?? []);
                $filterIndex = $arguments['filterIndex'] ?? null;

                if (is_int($filterIndex) && ($configuredFilters[$filterIndex]['type'] ?? null) === $filterName) {
                    $configuredFilters[$filterIndex]['data'] = $data;
                } else {
                    $configuredFilters[] = [
                        'type' => $filterName,
                        'data' => $data,
                    ];
                }

                $component->runCommands(
                    [EditorCommand::make('updateTwigApply', arguments: [[
                        'filters' => $this->normalizeBlockFilters($configuredFilters, $component),
                    ]])],
                    editorSelection: $arguments['editorSelection'],
                );
            });
    }

    private function configureConditionOperandFilterAction(): Action
    {
        return $this
            ->configureFilterModal(Action::make('configureTwigConditionOperandFilter'))
            ->modalHeading(function (array $arguments, TwigEditor $component) {
                $filter = $this->getFilterFromArguments($arguments, $component);

                return __('phpinnacle-stylus::forms.twig_editor.modals.filter', [
                    'filter' => $filter?->getLabel() ?? '',
                ]);
            })
            ->modalSubmitActionLabel(__('phpinnacle-stylus::forms.twig_editor.actions.apply'))
            ->modalWidth(Width::Large)
            ->fillForm(function (array $arguments) {
                $operand = $this->conditionOperandFromArguments($arguments);
                $filters = is_array($operand['filters'] ?? null) ? array_values($operand['filters']) : [];
                $filterIndex = $arguments['filterIndex'] ?? null;
                $filter = is_int($filterIndex) && is_array($filters[$filterIndex] ?? null)
                    ? $filters[$filterIndex]
                    : [];

                return (
                    is_array($filter['configuration'] ?? null)
                        ? $filter['configuration']
                        : []
                );
            })
            ->schema(function (array $arguments, TwigEditor $component) {
                $filterName = $arguments['filterName'] ?? null;

                return (
                    is_string($filterName)
                        ? $component->getFilterSchema($filterName)
                        : []
                );
            })
            ->action($this->applyConditionOperandFilter(...));
    }

    /** @param array<string, mixed> $arguments */
    private function applyConditionOperandFilter(array $arguments, array $data, TwigEditor $component): void
    {
        $conditionAst = is_array($arguments['conditionAst'] ?? null)
            ? $arguments['conditionAst']
            : [];
        $children = is_array($conditionAst['children'] ?? null)
            ? array_values($conditionAst['children'])
            : [];
        $conditionIndex = $arguments['conditionIndex'] ?? null;
        $operandKey = $arguments['operandKey'] ?? null;
        $clause = is_int($conditionIndex) && is_array($children[$conditionIndex] ?? null)
            ? $children[$conditionIndex]
            : null;
        $operand = is_string($operandKey) && is_array($clause[$operandKey] ?? null)
            ? $clause[$operandKey]
            : null;
        $variableName = is_array($operand) && ($operand['type'] ?? null) === 'variable'
            ? $operand['name'] ?? null
            : null;
        $variable = is_string($variableName)
            ? $component->getVariable($variableName, $this->getLoopStack($arguments))
            : null;
        $filterName = $arguments['filterName'] ?? null;
        $filter = is_string($filterName) ? $component->getFilter($filterName) : null;

        if (
            !$variable
            || !$filter
            || !$filter->supports($variable->getType())
            || $filter->getOutput() !== FilterOutput::Same
            || $filter->getSchema() === []
        ) {
            throw ValidationException::withMessages([
                'filters' => __('phpinnacle-stylus::forms.twig_editor.validation.filter_unavailable'),
            ]);
        }

        $configuredFilters = $this->filtersToFormState($operand['filters'] ?? []);
        $filterIndex = $arguments['filterIndex'] ?? null;

        if (is_int($filterIndex) && ($configuredFilters[$filterIndex]['type'] ?? null) === $filterName) {
            $configuredFilters[$filterIndex]['data'] = $data;
        } else {
            $configuredFilters[] = [
                'type' => $filterName,
                'data' => $data,
            ];
        }

        $clause[$operandKey]['filters'] = $this->normalizeConditionFilters(
            $configuredFilters,
            $variable,
            $component,
        );
        $children[$conditionIndex] = $clause;
        $conditionAst['children'] = $children;

        try {
            $condition = $component->serializeCondition($conditionAst, $this->getLoopStack($arguments));
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'filters' => __('phpinnacle-stylus::forms.twig_editor.validation.condition_invalid'),
            ]);
        }

        $command = match ($arguments['conditionTarget'] ?? null) {
            'row' => 'setTwigTableRowCondition',
            default => ($arguments['inline'] ?? false) === true
                ? 'updateTwigInlineIf'
                : 'updateTwigIf',
        };

        $component->runCommands(
            [EditorCommand::make($command, arguments: [[
                'condition' => $condition,
                'conditionAst' => $conditionAst,
            ]])],
            editorSelection: $arguments['editorSelection'],
        );
    }

    private function configureConditionRuleModal(Action $action): Action
    {
        return $action
            ->modalIcon(function (array $arguments, TwigEditor $component) {
                $conditionType = $arguments['conditionType'] ?? null;
                $condition = is_string($conditionType)
                    ? $component->getCondition($conditionType)
                    : null;

                return $condition?->getIcon() ?? match ($condition?->getType()) {
                    ConditionKind::Truthy => 'heroicon-o-check-circle',
                    ConditionKind::Comparison => 'heroicon-o-arrows-right-left',
                    ConditionKind::Test => 'heroicon-o-beaker',
                    default => null,
                };
            })
            ->modalIconColor(function (array $arguments, TwigEditor $component) {
                $conditionType = $arguments['conditionType'] ?? null;

                return is_string($conditionType)
                    ? $component->getCondition($conditionType)?->getColor() ?? 'gray'
                    : 'gray';
            })
            ->modalDescription(function (array $arguments, TwigEditor $component) {
                $conditionType = $arguments['conditionType'] ?? null;

                return is_string($conditionType)
                    ? $component->getCondition($conditionType)?->getDescription()
                    : null;
            });
    }

    private function configureFilterModal(Action $action): Action
    {
        return $action
            ->modalIcon(
                fn (array $arguments, TwigEditor $component) => (
                    $this->getFilterFromArguments($arguments, $component)?->getIcon() ?? 'heroicon-o-funnel'
                ),
            )
            ->modalIconColor(
                fn (array $arguments, TwigEditor $component) => (
                    $this->getFilterFromArguments($arguments, $component)?->getColor() ?? 'gray'
                ),
            )
            ->modalDescription(
                fn (array $arguments, TwigEditor $component) => $this->getFilterFromArguments(
                    $arguments,
                    $component,
                )?->getDescription(),
            );
    }

    private function configureLoopFilterAction(): Action
    {
        return $this
            ->configureFilterModal(Action::make('configureTwigLoopFilter'))
            ->modalHeading(function (array $arguments, TwigEditor $component) {
                $filter = $this->getFilterFromArguments($arguments, $component);

                return __('phpinnacle-stylus::forms.twig_editor.modals.filter', [
                    'filter' => $filter?->getLabel() ?? '',
                ]);
            })
            ->modalSubmitActionLabel(__('phpinnacle-stylus::forms.twig_editor.actions.apply'))
            ->modalWidth(Width::Large)
            ->fillForm(function (array $arguments) {
                $filterIndex = $arguments['filterIndex'] ?? null;
                $filters = is_array($arguments['filters'] ?? null) ? $arguments['filters'] : [];
                $filter = is_int($filterIndex) && is_array($filters[$filterIndex] ?? null)
                    ? $filters[$filterIndex]
                    : [];

                return (
                    is_array($filter['configuration'] ?? null)
                        ? $filter['configuration']
                        : []
                );
            })
            ->schema(function (array $arguments, TwigEditor $component) {
                $filterName = $arguments['filterName'] ?? null;

                return (
                    is_string($filterName)
                        ? $component->getFilterSchema($filterName)
                        : []
                );
            })
            ->action($this->applyLoopFilter(...));
    }

    /** @param array<string, mixed> $arguments */
    private function applyLoopFilter(array $arguments, array $data, TwigEditor $component): void
    {
        $filterName = $arguments['filterName'] ?? null;
        $filter = is_string($filterName) ? $component->getFilter($filterName) : null;

        if (
            !$filter?->supports('collection')
            || $filter->getOutput() === FilterOutput::CollectionItem
            || $filter->getSchema() === []
        ) {
            throw ValidationException::withMessages([
                'filters' => __('phpinnacle-stylus::forms.twig_editor.validation.collection_filter_unavailable'),
            ]);
        }

        $configuredFilters = $this->filtersToFormState($arguments['filters'] ?? []);
        $filterIndex = $arguments['filterIndex'] ?? null;

        if (is_int($filterIndex) && ($configuredFilters[$filterIndex]['type'] ?? null) === $filterName) {
            $configuredFilters[$filterIndex]['data'] = $data;
        } else {
            $configuredFilters[] = [
                'type' => $filterName,
                'data' => $data,
            ];
        }

        $item = trim((string) ($arguments['item'] ?? ''));
        $key = is_string($arguments['key'] ?? null) ? trim($arguments['key']) : null;
        $iterable = trim((string) ($arguments['iterable'] ?? ''));

        if (
            preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $item) !== 1
            || filled($key)
            && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key) !== 1
        ) {
            throw ValidationException::withMessages([
                'filters' => __('phpinnacle-stylus::forms.twig_editor.validation.loop_identifier'),
            ]);
        }

        $scope = $component->getVariableScope($this->getLoopStack($arguments));

        if (
            blank($iterable)
            || mb_strlen($iterable) > 500
            || filled($scope->getIterableOptions())
            && !$scope->getVariable($iterable)?->isCollection()
        ) {
            throw ValidationException::withMessages([
                'filters' => __('phpinnacle-stylus::forms.twig_editor.validation.iterable_unavailable'),
            ]);
        }

        $command = match ($arguments['loopTarget'] ?? 'block') {
            'row' => 'setTwigTableRowLoop',
            'cell' => 'setTwigTableCellLoop',
            default => 'updateTwigFor',
        };

        $component->runCommands(
            [EditorCommand::make($command, arguments: [[
                'item' => $item,
                'key' => filled($key) ? $key : null,
                'iterable' => $iterable,
                'transforms' => $this->normalizeCollectionFilters($configuredFilters, $component),
            ]])],
            editorSelection: $arguments['editorSelection'],
        );
    }

    private function configureVariableFilterAction(): Action
    {
        return $this
            ->configureFilterModal(Action::make('configureTwigVariableFilter'))
            ->modalHeading(function (array $arguments, TwigEditor $component) {
                $filter = $this->getFilterFromArguments($arguments, $component);

                return __('phpinnacle-stylus::forms.twig_editor.modals.filter', [
                    'filter' => $filter?->getLabel() ?? '',
                ]);
            })
            ->modalSubmitActionLabel(__('phpinnacle-stylus::forms.twig_editor.actions.apply'))
            ->modalWidth(Width::Large)
            ->fillForm(function (array $arguments) {
                $filterIndex = $arguments['filterIndex'] ?? null;
                $filters = is_array($arguments['filters'] ?? null) ? $arguments['filters'] : [];
                $filter = is_int($filterIndex) && is_array($filters[$filterIndex] ?? null)
                    ? $filters[$filterIndex]
                    : [];

                return (
                    is_array($filter['configuration'] ?? null)
                        ? $filter['configuration']
                        : []
                );
            })
            ->schema(function (array $arguments, TwigEditor $component) {
                $filterName = $arguments['filterName'] ?? null;

                return (
                    is_string($filterName)
                        ? $component->getFilterSchema($filterName)
                        : []
                );
            })
            ->action(function (array $arguments, array $data, TwigEditor $component) {
                $variable = $this->resolveVariable($arguments, $component);
                $filterName = $arguments['filterName'] ?? null;
                $filter = is_string($filterName) ? $component->getFilter($filterName) : null;

                if (!$filter || !$filter->supports($variable->getType()) || $filter->getSchema() === []) {
                    throw ValidationException::withMessages([
                        'filters' => __('phpinnacle-stylus::forms.twig_editor.validation.filter_unavailable'),
                    ]);
                }

                $configuredFilters = $this->filtersToFormState($arguments['filters'] ?? []);
                $filterIndex = $arguments['filterIndex'] ?? null;

                if (is_int($filterIndex) && ($configuredFilters[$filterIndex]['type'] ?? null) === $filterName) {
                    $configuredFilters[$filterIndex]['data'] = $data;
                } else {
                    $configuredFilters[] = [
                        'type' => $filterName,
                        'data' => $data,
                    ];
                }

                $this->updateVariableFilters(
                    $arguments,
                    $this->normalizeFilters($configuredFilters, $variable, $component),
                    $variable,
                    $component,
                );
            });
    }

    private function filterBlock(Filter $filter): Block
    {
        $schema = $filter->getSchema();
        $block = Block::make($filter->getName())
            ->label($filter->getLabel())
            ->icon(generate_icon_html(
                $filter->getIcon(),
                attributes: new FilamentComponentAttributeBag()
                    ->class(['fi-stylus-twig-metadata-icon'])
                    ->color(IconComponent::class, $filter->getColor()),
            ))
            ->schema($schema);

        if ($schema === []) {
            $block->extraAttributes([
                'class' => 'fi-stylus-twig-filter-parameterless',
            ]);
        }

        return $block;
    }

    /** @return list<array{type: mixed, data: array<string, mixed>}> */
    private function filtersToFormState(mixed $filters): array
    {
        if (!is_array($filters)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $filter) => (
                is_array($filter)
                    ? [
                        'type' => $filter['name'] ?? null,
                        'data' => is_array($filter['configuration'] ?? null)
                            ? $filter['configuration']
                            : [],
                    ]
                    : [
                        'type' => null,
                        'data' => [],
                    ]
            ),
            $filters,
        ));
    }

    /** @param array<string, mixed> $arguments */
    private function getFilterFromArguments(array $arguments, TwigEditor $component): ?Filter
    {
        $filterName = $arguments['filterName'] ?? null;

        return is_string($filterName) ? $component->getFilter($filterName) : null;
    }

    /** @return array<int, mixed> */
    private function getLoopStack(array $arguments): array
    {
        return (
            is_array($arguments['loopStack'] ?? null)
                ? array_values($arguments['loopStack'])
                : []
        );
    }

    private function insertApplyFilterAction(): Action
    {
        return $this
            ->configureFilterModal(Action::make('insertTwigApply'))
            ->modalHidden(function (array $arguments, TwigEditor $component) {
                $filter = $this->getFilterFromArguments($arguments, $component);

                return $filter?->getSchema() === [];
            })
            ->modalHeading(function (array $arguments, TwigEditor $component) {
                $filter = $this->getFilterFromArguments($arguments, $component);

                return __('phpinnacle-stylus::forms.twig_editor.modals.filter', [
                    'filter' => $filter?->getLabel() ?? '',
                ]);
            })
            ->modalSubmitActionLabel(__('phpinnacle-stylus::forms.twig_editor.actions.apply'))
            ->modalWidth(Width::Large)
            ->schema(function (array $arguments, TwigEditor $component) {
                $filterName = $arguments['filterName'] ?? null;

                return (
                    is_string($filterName)
                        ? $component->getFilterSchema($filterName)
                        : []
                );
            })
            ->action(function (array $arguments, array $data, TwigEditor $component) {
                $filterName = $arguments['filterName'] ?? null;
                $filter = is_string($filterName) ? $component->getFilter($filterName) : null;

                if (!$filter?->supportsBlocks()) {
                    throw ValidationException::withMessages([
                        'filters' => __('phpinnacle-stylus::forms.twig_editor.validation.block_filter_unavailable'),
                    ]);
                }

                $component->runCommands(
                    [EditorCommand::make('insertTwigApply', arguments: [[
                        'filters' => $this->normalizeBlockFilters([[
                            'type' => $filterName,
                            'data' => $data,
                        ]], $component),
                    ]])],
                    editorSelection: $arguments['editorSelection'],
                );
            });
    }

    private function insertConditionRuleAction(string $name, bool $tableRow = false): Action
    {
        return $this
            ->configureConditionRuleModal(Action::make($name))
            ->modalHeading(function (array $arguments, TwigEditor $component) {
                $conditionType = $arguments['conditionType'] ?? null;

                return __('phpinnacle-stylus::forms.twig_editor.modals.condition_rule', [
                    'condition' => is_string($conditionType)
                        ? $this->conditionLabel($conditionType, $component)
                        : '',
                ]);
            })
            ->modalSubmitActionLabel(__('phpinnacle-stylus::forms.twig_editor.actions.insert'))
            ->modalWidth(Width::Large)
            ->schema(function (array $arguments, TwigEditor $component) {
                $conditionType = $arguments['conditionType'] ?? null;

                return (
                    is_string($conditionType)
                        ? $this->conditionRuleSchema(
                            $conditionType,
                            $component,
                            $this->getLoopStack($arguments),
                        )
                        : []
                );
            })
            ->action(function (array $arguments, array $data, TwigEditor $component) use ($tableRow) {
                $conditionType = $arguments['conditionType'] ?? null;

                $condition = is_string($conditionType)
                    ? $component->getCondition($conditionType)
                    : null;

                if (!$condition) {
                    throw ValidationException::withMessages([
                        'condition' => __('phpinnacle-stylus::forms.twig_editor.validation.condition_unavailable'),
                    ]);
                }

                $clause = $this->conditionFormClauseToAst($condition, $data);
                $clause['negated'] = false;
                $conditionAst = [
                    'type' => 'group',
                    'operator' => 'and',
                    'negated' => false,
                    'children' => [$clause],
                ];

                try {
                    $condition = $component->serializeCondition(
                        $conditionAst,
                        $this->getLoopStack($arguments),
                    );
                } catch (InvalidArgumentException) {
                    throw ValidationException::withMessages([
                        $this->conditionValidationField($condition) => __(
                            'phpinnacle-stylus::forms.twig_editor.validation.condition_invalid',
                        ),
                    ]);
                }

                $isInline = ($arguments['inline'] ?? false) === true;
                $isTableRow = $tableRow || ($arguments['conditionTarget'] ?? null) === 'row';
                $attributes = [
                    'condition' => $condition,
                    'conditionAst' => $conditionAst,
                    ...($isInline || $isTableRow ? [] : ['hasElse' => false]),
                ];

                $component->runCommands(
                    [EditorCommand::make(
                        match (true) {
                            $isTableRow => 'setTwigTableRowCondition',
                            $isInline => 'insertTwigInlineIf',
                            default => 'insertTwigIf',
                        },
                        arguments: [$attributes],
                    )],
                    editorSelection: $arguments['editorSelection'],
                );
            });
    }

    private function insertTableLoopAction(string $name, string $command): Action
    {
        return Action::make($name)
            ->modal(false)
            ->action(function (array $arguments, TwigEditor $component) use ($command) {
                $iterable = $arguments['iterable'] ?? null;
                $scope = $component->getVariableScope($this->getLoopStack($arguments));
                $variable = is_string($iterable) ? $scope->getVariable($iterable) : null;

                if (!$variable?->isCollection()) {
                    throw ValidationException::withMessages([
                        'iterable' => __('phpinnacle-stylus::forms.twig_editor.validation.iterable_unavailable'),
                    ]);
                }

                $component->runCommands(
                    [EditorCommand::make($command, arguments: [[
                        'item' => $variable->getItem()?->getName() ?? 'item',
                        'key' => null,
                        'iterable' => $variable->getName(),
                        'transforms' => [],
                        'hasElse' => false,
                    ]])],
                    editorSelection: $arguments['editorSelection'],
                );
            });
    }

    private function loopAction(string $name, string $command): Action
    {
        return $this->loopConfigurationAction(
            $name,
            $command,
            __('phpinnacle-stylus::forms.twig_editor.modals.loop'),
            static fn (array $arguments) => false,
            supportsElse: true,
        );
    }

    /** @param callable(array<string, mixed>): bool $isExisting */
    private function loopConfigurationAction(
        string $name,
        string $command,
        string $heading,
        callable $isExisting,
        bool $supportsElse,
    ): Action {
        return Action::make($name)
            ->modalHeading($heading)
            ->modalSubmitActionLabel(__('phpinnacle-stylus::forms.twig_editor.actions.apply'))
            ->modalWidth(Width::Large)
            ->fillForm($this->loopFormState(...))
            ->schema(function (array $arguments, TwigEditor $component) use ($isExisting, $supportsElse) {
                $fields = [
                    Group::make([
                        TextInput::make('item')
                            ->label(__('phpinnacle-stylus::forms.twig_editor.fields.loop_item'))
                            ->regex('/^[A-Za-z_][A-Za-z0-9_]*$/')
                            ->required(),
                        TextInput::make('key')
                            ->label(__('phpinnacle-stylus::forms.twig_editor.fields.loop_key'))
                            ->regex('/^[A-Za-z_][A-Za-z0-9_]*$/')
                            ->nullable(),
                    ])->columns(2),
                    ...(
                        $supportsElse
                            ? [
                                Toggle::make('hasElse')
                                    ->label(__('phpinnacle-stylus::forms.twig_editor.fields.has_empty_state')),
                            ] : []
                    ),
                ];

                if ($isExisting($arguments)) {
                    return [
                        ...$fields,
                        ...$this->collectionFilterFields($component),
                    ];
                }

                $scope = $component->getVariableScope($this->getLoopStack($arguments));
                $options = $scope->getIterableOptions();
                $currentIterable = $arguments['iterable'] ?? null;

                if (
                    is_string($currentIterable)
                    && filled($currentIterable)
                    && !array_key_exists($currentIterable, $options)
                ) {
                    $options[$currentIterable] = $currentIterable;
                }

                $iterableField = filled($options)
                    ? Select::make('iterable')
                        ->options($options)
                        ->native()
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set) use ($scope) {
                            $item = is_string($state) ? $scope->getVariable($state)?->getItem() : null;

                            if ($item) {
                                $set('item', $item->getName());
                            }
                        })
                    : TextInput::make('iterable')
                        ->maxLength(500);

                return [
                    ...$fields,
                    $iterableField
                        ->label(__('phpinnacle-stylus::forms.twig_editor.fields.iterable'))
                        ->helperText(__('phpinnacle-stylus::forms.twig_editor.helpers.iterable'))
                        ->required(),
                    ...$this->collectionFilterFields($component),
                ];
            })
            ->action(function (array $arguments, array $data, TwigEditor $component) use (
                $command,
                $isExisting,
                $supportsElse,
            ) {
                $key = is_string($data['key'] ?? null) ? trim($data['key']) : null;
                $scope = $component->getVariableScope($this->getLoopStack($arguments));
                $currentIterable = $arguments['iterable'] ?? null;
                $existing = $isExisting($arguments);
                $iterable = $existing && is_string($currentIterable)
                    ? $currentIterable
                    : trim((string) ($data['iterable'] ?? ''));

                if (
                    filled($scope->getIterableOptions())
                    && !$scope->getVariable($iterable)?->isCollection()
                    && (!$existing
                    || $iterable !== $currentIterable)
                ) {
                    throw ValidationException::withMessages([
                        'iterable' => __('phpinnacle-stylus::forms.twig_editor.validation.iterable_unavailable'),
                    ]);
                }

                $hasElse = $supportsElse && (bool) ($data['hasElse'] ?? false);
                $component->runCommands(
                    [EditorCommand::make($command, arguments: [[
                        'item' => trim((string) $data['item']),
                        'key' => filled($key) ? $key : null,
                        'iterable' => $iterable,
                        'transforms' => $this->normalizeCollectionFilters($data['filters'] ?? [], $component),
                        'hasElse' => $hasElse,
                    ]])],
                    editorSelection: $arguments['editorSelection'],
                );
            });
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function loopFormState(array $arguments): array
    {
        return [
            'item' => $arguments['item'] ?? 'item',
            'key' => $arguments['key'] ?? null,
            'iterable' => $arguments['iterable'] ?? '',
            'filters' => array_map(
                static fn (mixed $filter) => (
                    is_array($filter)
                        ? [
                            'type' => $filter['name'] ?? null,
                            'data' => is_array($filter['configuration'] ?? null)
                                ? $filter['configuration']
                                : [],
                        ]
                        : []
                ),
                is_array($arguments['transforms'] ?? null) ? $arguments['transforms'] : [],
            ),
            'hasElse' => (bool) ($arguments['hasElse'] ?? false),
        ];
    }

    /** @return list<array{name: string, arguments: list<string>, configuration: array<string, mixed>}> */
    private function normalizeBlockFilters(mixed $configuredFilters, TwigEditor $component): array
    {
        if (!is_array($configuredFilters)) {
            return [];
        }

        $filters = [];

        foreach ($configuredFilters as $configuredFilter) {
            $filterName = is_array($configuredFilter) ? $configuredFilter['type'] ?? null : null;
            $filter = is_string($filterName) ? $component->getFilter($filterName) : null;

            if (!$filter?->supportsBlocks()) {
                throw ValidationException::withMessages([
                    'filters' => __('phpinnacle-stylus::forms.twig_editor.validation.block_filter_unavailable'),
                ]);
            }

            $configuration = is_array($configuredFilter['data'] ?? null)
                ? $configuredFilter['data']
                : [];

            $filters[] = [
                'name' => $filter->getName(),
                'arguments' => $filter->getArguments($configuration),
                'configuration' => $configuration,
            ];
        }

        return $filters;
    }

    /** @return list<array{name: string, arguments: list<string>, configuration: array<string, mixed>}> */
    private function normalizeCollectionFilters(mixed $configuredFilters, TwigEditor $component): array
    {
        if (!is_array($configuredFilters)) {
            return [];
        }

        $filters = [];

        foreach ($configuredFilters as $configuredFilter) {
            $filterName = is_array($configuredFilter) ? $configuredFilter['type'] ?? null : null;
            $filter = is_string($filterName)
                ? $component->getFilter($filterName)
                : null;

            if (
                !$filter?->supports('collection')
                || $filter->getOutput() === FilterOutput::CollectionItem
            ) {
                throw ValidationException::withMessages([
                    'filters' => __('phpinnacle-stylus::forms.twig_editor.validation.filter_unavailable'),
                ]);
            }

            $configuration = is_array($configuredFilter['data'] ?? null)
                ? $configuredFilter['data']
                : [];

            $filters[] = [
                'name' => $filter->getName(),
                'arguments' => $filter->getArguments($configuration),
                'configuration' => $configuration,
            ];
        }

        return $filters;
    }

    /** @return list<array{name: string, arguments: list<string>, configuration: array<string, mixed>}> */
    private function normalizeConditionFilters(
        mixed $configuredFilters,
        Variable $variable,
        TwigEditor $component,
    ): array {
        $filters = $this->normalizeFilters($configuredFilters, $variable, $component);

        foreach ($filters as $filterState) {
            $filter = $component->getFilter($filterState['name']);

            if ($filter?->getOutput() !== FilterOutput::Same) {
                throw ValidationException::withMessages([
                    'filters' => __('phpinnacle-stylus::forms.twig_editor.validation.filter_unavailable'),
                ]);
            }
        }

        return $filters;
    }

    /**
     * @return list<array{name: string, arguments: list<string>, configuration: array<string, mixed>}>
     */
    private function normalizeFilters(mixed $configuredFilters, Variable $variable, TwigEditor $component): array
    {
        if (!is_array($configuredFilters)) {
            return [];
        }

        $filters = [];

        foreach ($configuredFilters as $configuredFilter) {
            $filterName = is_array($configuredFilter) ? $configuredFilter['type'] ?? null : null;
            $filter = is_string($filterName) ? $component->getFilter($filterName) : null;

            if (!$filter || !$filter->supports($variable->getType())) {
                throw ValidationException::withMessages([
                    'filters' => __('phpinnacle-stylus::forms.twig_editor.validation.filter_unavailable'),
                ]);
            }

            $configuration = is_array($configuredFilter['data'] ?? null)
                ? $configuredFilter['data']
                : [];

            $filters[] = [
                'name' => $filter->getName(),
                'arguments' => $filter->getArguments($configuration),
                'configuration' => $configuration,
            ];
        }

        return $filters;
    }

    /** @param array<string, mixed> $arguments */
    private function resolveVariable(array $arguments, TwigEditor $component): Variable
    {
        $expression = $arguments['expression'] ?? null;
        $variable = is_string($expression)
            ? $component->getVariable($expression, $this->getLoopStack($arguments))
            : null;

        if (!$variable) {
            throw ValidationException::withMessages([
                'expression' => __('phpinnacle-stylus::forms.twig_editor.validation.variable_unavailable'),
            ]);
        }

        return $variable;
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @param  list<array{name: string, arguments: list<string>, configuration: array<string, mixed>}>  $filters
     */
    private function updateVariableFilters(
        array $arguments,
        array $filters,
        Variable $variable,
        TwigEditor $component,
    ): void {
        $component->runCommands(
            [EditorCommand::make('updateTwigVariable', arguments: [[
                'expression' => $variable->getName(),
                'filters' => $filters,
            ]])],
            editorSelection: $arguments['editorSelection'],
        );
    }
}
