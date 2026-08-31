<?php

namespace PHPinnacle\Stylus\Forms;

use Filament\Forms\Components\RichEditor as BaseRichEditor;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\View\Components\IconComponent;
use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use InvalidArgumentException;
use PHPinnacle\Stylus\Condition as ConditionDescriptor;
use PHPinnacle\Stylus\Contracts\Condition;
use PHPinnacle\Stylus\Contracts\Filter;
use PHPinnacle\Stylus\Contracts\Variable;
use PHPinnacle\Stylus\Enums\ConditionKind;
use PHPinnacle\Stylus\Enums\FilterOutput;
use PHPinnacle\Stylus\Forms\StateCasts\TwigEditorStateCast;
use PHPinnacle\Stylus\RichEditor\TwigTemplatePlugin;
use PHPinnacle\Stylus\Snippet;
use PHPinnacle\Stylus\Twig\ConditionExpressionSerializer;
use PHPinnacle\Stylus\VariableScope;

use function Filament\Support\generate_icon_html;

class TwigEditor extends BaseRichEditor
{
    public const int DOCUMENT_VERSION = 1;

    /** @var array<string, Variable> */
    private array $variables = [];

    /** @var array<string, Variable> */
    private array $variableDefinitions = [];

    /** @var array<string, Filter> */
    private array $filters = [];

    /** @var array<string, Condition> */
    private array $conditions = [];

    /** @var array<string, Snippet> */
    private array $snippets = [];

    /** @return array<string, mixed> */
    public static function emptyDocument(): array
    {
        return [
            'type' => 'doc',
            'content' => [
                ['type' => 'paragraph'],
            ],
        ];
    }

    /** @param array<string, mixed> $document */
    public function compileStructuredConditions(array $document): array
    {
        return $this->compileConditionNode($document, []);
    }

    public function conditions(Condition ...$conditions): static
    {
        $truthy = $this->makeTruthyCondition();
        $this->conditions = [$truthy->getKey() => $truthy];

        foreach ($conditions as $condition) {
            $this->conditions[$condition->getKey()] = $condition;
        }

        $this->cachedTools = null;

        return $this;
    }

    public function filters(Filter ...$filters): static
    {
        $this->filters = [];

        foreach ($filters as $filter) {
            $this->filters[$filter->getName()] = $filter;
        }

        return $this;
    }

    /** @return array<string, Filter> */
    public function getBlockFilters(): array
    {
        return array_filter(
            $this->filters,
            static fn (Filter $filter) => $filter->supportsBlocks(),
        );
    }

    /** @return array<string, Filter> */
    public function getCollectionFilters(): array
    {
        return array_filter(
            $this->filters,
            static fn (Filter $filter) => (
                $filter->supports('collection')
                && $filter->getOutput() !== FilterOutput::CollectionItem
            ),
        );
    }

    public function getCondition(string $key): ?Condition
    {
        return $this->conditions[$key] ?? null;
    }

    /** @return list<array{key: string, name: string, type: string, expression: string|null, types: list<string>, matchVariableTypes: bool, label: string, description: string|null, color: string|null, iconHtml: string|null}> */
    public function getConditionDefinitionsForBrowser(): array
    {
        $definitions = [];

        foreach ($this->conditions as $condition) {
            $definitions[] = [
                'key' => $condition->getKey(),
                'name' => $condition->getName(),
                'type' => $condition->getType()->value,
                'expression' => $condition->getType() === ConditionKind::Truthy
                    ? null
                    : $condition->getExpression(),
                'types' => $condition->getTypes(),
                'matchVariableTypes' => $condition->matchesVariableTypes(),
                'label' => $condition->getLabel(),
                'description' => $condition->getDescription(),
                'color' => is_string($condition->getColor()) ? $condition->getColor() : null,
                'iconHtml' => generate_icon_html(
                    $condition->getIcon() ?? match ($condition->getType()) {
                        ConditionKind::Truthy => 'heroicon-o-check-circle',
                        ConditionKind::Comparison => 'heroicon-o-arrows-right-left',
                        ConditionKind::Test => 'heroicon-o-beaker',
                    },
                    attributes: new FilamentComponentAttributeBag()
                        ->class(['fi-stylus-twig-metadata-icon'])
                        ->color(IconComponent::class, $condition->getColor()),
                )?->toHtml(),
            ];
        }

        return $definitions;
    }

    /** @return array<string, Condition> */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /** @return array<StateCast> */
    public function getDefaultStateCasts(): array
    {
        return [
            app(TwigEditorStateCast::class, ['twigEditor' => $this]),
        ];
    }

    /**
     * @return array<string | array<string | ToolbarButtonGroup>>
     */
    public function getDefaultToolbarButtons(): array
    {
        return [
            ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
            [
                ToolbarButtonGroup::make(
                    __('phpinnacle-stylus::forms.twig_editor.tools.text_style'),
                    ['paragraph', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
                )->textualButtons(),
            ],
            ['alignStart', 'alignCenter', 'alignEnd'],
            ['blockquote', 'table', 'bulletList', 'orderedList'],
            ...(filled($this->variables) ? [['twigVariables']] : []),
            [
                ToolbarButtonGroup::make(
                    __('phpinnacle-stylus::forms.twig_editor.tools.insert_condition'),
                    $this->getConditionRuleToolNames(),
                )
                    ->icon('heroicon-o-arrows-right-left')
                    ->textualButtons(),
                ...(filled($this->getBlockFilters()) ? ['insertTwigApply'] : []),
                ...(filled($this->snippets) ? ['insertTwigSnippet'] : []),
                ToolbarButtonGroup::make(
                    __('phpinnacle-stylus::forms.twig_editor.tools.condition_view'),
                    ['twigConditionViewBoth', 'twigConditionViewIf', 'twigConditionViewElse'],
                )->textualButtons(),
                'twigOutline',
                'twigTemplateStructure',
            ],
            ['undo', 'redo'],
        ];
    }

    public function getFilter(string $name): ?Filter
    {
        return $this->filters[$name] ?? null;
    }

    /** @return list<array{name: string, label: string, description: string|null, types: list<string>, configurable: bool, collectionCompatible: bool, blockCompatible: bool, conditionCompatible: bool, color: string|null, iconHtml: string|null}> */
    public function getFilterDefinitionsForBrowser(): array
    {
        return array_map(
            static fn (Filter $filter) => [
                'name' => $filter->getName(),
                'label' => $filter->getLabel(),
                'description' => $filter->getDescription(),
                'types' => $filter->getTypes(),
                'configurable' => $filter->getSchema() !== [],
                'collectionCompatible' =>
                    $filter->supports('collection') && $filter->getOutput() !== FilterOutput::CollectionItem,
                'blockCompatible' => $filter->supportsBlocks(),
                'conditionCompatible' => $filter->getOutput() === FilterOutput::Same,
                'color' => is_string($filter->getColor()) ? $filter->getColor() : null,
                'iconHtml' => generate_icon_html(
                    $filter->getIcon(),
                    attributes: new FilamentComponentAttributeBag()
                        ->class(['fi-stylus-twig-metadata-icon'])
                        ->color(IconComponent::class, $filter->getColor()),
                )?->toHtml(),
            ],
            array_values($this->filters),
        );
    }

    /**
     * @param  array<int, mixed>  $loopStack
     * @return array<string, string>
     */
    public function getFilterOptionsForVariable(string $variableName, array $loopStack = []): array
    {
        $variable = $this->getVariable($variableName, $loopStack);

        if (!$variable) {
            return [];
        }

        $options = [];

        foreach ($this->filters as $filter) {
            if ($filter->supports($variable->getType())) {
                $options[$filter->getName()] = $filter->getLabel();
            }
        }

        return $options;
    }

    /** @return array<string, Filter> */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /** @return list<Component> */
    public function getFilterSchema(string $filterName): array
    {
        return $this->getFilter($filterName)?->getSchema() ?? [];
    }

    /** @return array<string, list<Variable>> */
    public function getGroupedVariables(): array
    {
        $groups = [];

        foreach ($this->variables as $variable) {
            $groups[$variable->getGroup() ?? ''][] = $variable;
        }

        return $groups;
    }

    /**
     * @param  array<int, mixed>  $loopStack
     * @return array<string, string>
     */
    public function getIterableOptions(array $loopStack = []): array
    {
        return $this->getVariableScope($loopStack)->getIterableOptions();
    }

    /** @param array<int, mixed> $loopStack */
    public function getMissingSnippetVariables(Snippet $snippet, array $loopStack = []): array
    {
        $scope = $this->getVariableScope($loopStack);

        return array_values(array_filter(
            $snippet->requiredVariables,
            static fn (string $variable) => !$scope->getVariable($variable),
        ));
    }

    public function getSnippet(string $name): ?Snippet
    {
        return $this->snippets[$name] ?? null;
    }

    /** @return array<string, Snippet> */
    public function getSnippets(): array
    {
        return $this->snippets;
    }

    /** @param array<int, mixed> $loopStack */
    public function getVariable(string $name, array $loopStack = []): ?Variable
    {
        return $this->getVariableScope($loopStack)->getVariable($name);
    }

    /** @return list<array<string, mixed>> */
    public function getVariableDefinitions(): array
    {
        return array_map(
            static fn (Variable $variable) => $variable->toArray(),
            array_values($this->variableDefinitions),
        );
    }

    /** @return list<array{label: string, variables: list<array<string, mixed>>}> */
    public function getVariableGroupsForBrowser(): array
    {
        $groups = [];

        foreach ($this->getGroupedVariables() as $label => $variables) {
            $groups[] = [
                'label' => $label,
                'variables' => array_map(
                    static fn (Variable $variable) => $variable->toArray(),
                    $variables,
                ),
            ];
        }

        return $groups;
    }

    /** @return array<string, Variable> */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /** @param array<int, mixed> $loopStack */
    public function getVariableScope(array $loopStack = []): VariableScope
    {
        $scope = new VariableScope($this->variables);

        foreach ($loopStack as $loop) {
            if (!is_array($loop)) {
                break;
            }

            $item = $loop['item'] ?? null;
            $key = $loop['key'] ?? null;
            $iterable = $loop['iterable'] ?? null;

            if (
                !is_string($item)
                || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $item)
                || !is_null($key)
                && !is_string($key)
                || is_string($key)
                && !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)
                || !is_string($iterable)
                || blank($iterable)
            ) {
                break;
            }

            $scope->enterLoop(
                $item,
                $key,
                $iterable,
                __('phpinnacle-stylus::forms.twig_editor.panel.scope', ['variable' => $item]),
            );
        }

        return $scope;
    }

    /** @param array<string, mixed> $expression */
    public function serializeCondition(array $expression, array $loopStack = []): string
    {
        return new ConditionExpressionSerializer(
            $this->getVariableScope($loopStack),
            $this->conditions,
            $this->filters,
        )->serialize($expression);
    }

    public function snippets(Snippet ...$snippets): static
    {
        $this->snippets = [];

        foreach ($snippets as $snippet) {
            $this->snippets[$snippet->name] = $snippet;
        }

        return $this;
    }

    public function toEmbeddedHtml(): string
    {
        return view('stylus::forms.components.twig-editor', [
            'editorHtml' => new HtmlString(parent::toEmbeddedHtml()),
            'panelTarget' => md5($this->getLivewire()->getId() . '.' . $this->getStatePath()),
            'preferenceKey' => 'phpinnacle-stylus.twig-editor.' . $this->getStatePath(),
            'snippets' => array_map(
                static fn (Snippet $snippet) => $snippet->toArray(),
                array_values($this->snippets),
            ),
            'editorKey' => $this->getKey(),
            'conditionDefinitions' => $this->getConditionDefinitionsForBrowser(),
            'filterDefinitions' => $this->getFilterDefinitionsForBrowser(),
            'variableGroups' => $this->getVariableGroupsForBrowser(),
            'variableDefinitions' => $this->getVariableDefinitions(),
        ])->render();
    }

    public function variables(Variable ...$variables): static
    {
        $this->variables = [];
        $this->variableDefinitions = [];

        foreach ($variables as $variable) {
            foreach ($variable->flatten() as $name => $flattenedVariable) {
                $this->variables[$name] = $flattenedVariable;
            }

            $this->variableDefinitions[$variable->getName()] = $variable;
        }

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $truthy = $this->makeTruthyCondition();
        $this->conditions = [$truthy->getKey() => $truthy];

        $this
            ->json()
            ->tools(fn () => $this->getConditionRuleTools())
            ->plugins([
                TwigTemplatePlugin::make(),
            ])
            ->default([
                'version' => self::DOCUMENT_VERSION,
                'document' => self::emptyDocument(),
                'template' => '<p></p>',
            ])
            ->floatingToolbars(fn () => [
                'table' => [
                    'tableAddColumnBefore',
                    'tableAddColumnAfter',
                    'tableDeleteColumn',
                    'configureTwigTableCellLoop',
                    'tableAddRowBefore',
                    'tableAddRowAfter',
                    'tableDeleteRow',
                    'configureTwigTableRowLoop',
                    ToolbarButtonGroup::make(
                        __('phpinnacle-stylus::forms.twig_editor.tools.condition_rows'),
                        $this->getTableRowConditionRuleToolNames(),
                    )
                        ->icon('heroicon-o-arrows-right-left')
                        ->textualButtons()
                        ->extraAttributes([
                            'data-stylus-twig-panel-trigger' => 'row-condition',
                            'x-on:click' => <<<'JS'
                                if (window.PHPinnacleStylusTwigEditor?.hasTwigTableRowCondition($getEditor())) {
                                    open = false
                                    window.PHPinnacleStylusTwigEditor.openTwigTableRowCondition($getEditor())
                                } else {
                                    open = ! open
                                }
                                JS,
                        ]),
                    'tableMergeCells',
                    'tableSplitCell',
                    'tableToggleHeaderRow',
                    'tableToggleHeaderCell',
                    'tableDelete',
                ],
            ]);
    }

    /**
     * @param  array<int, mixed>  $loopStack
     * @param  array<string, mixed>  $attributes
     * @return array<int, mixed>
     */
    private function appendAttributeLoop(array $loopStack, array $attributes): array
    {
        if (!filled($attributes['twigLoopIterable'] ?? null)) {
            return $loopStack;
        }

        $loopStack[] = [
            'item' => $attributes['twigLoopItem'] ?? null,
            'key' => $attributes['twigLoopKey'] ?? null,
            'iterable' => $attributes['twigLoopIterable'],
        ];

        return $loopStack;
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<int, mixed>  $loopStack
     * @return array<string, mixed>
     */
    private function compileConditionNode(array $node, array $loopStack): array
    {
        $attributes = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];

        if (($node['type'] ?? null) === 'twigIf') {
            if (!is_array($attributes['conditionAst'] ?? null)) {
                throw new InvalidArgumentException('Twig condition nodes must contain a structured condition AST.');
            }

            $attributes['condition'] = $this->serializeCondition($attributes['conditionAst'], $loopStack);
            $node['attrs'] = $attributes;
        }

        $hasTableCondition =
            filled($attributes['twigCondition'] ?? null)
            || filled($attributes['twigConditionId'] ?? null)
            || is_array($attributes['twigConditionAst'] ?? null);

        if (($node['type'] ?? null) === 'tableRow' && $hasTableCondition) {
            if (!is_array($attributes['twigConditionAst'] ?? null)) {
                throw new InvalidArgumentException(
                    'Twig table row conditions must contain a structured condition AST.',
                );
            }

            $attributes['twigCondition'] = $this->serializeCondition(
                $attributes['twigConditionAst'],
                $this->appendAttributeLoop($loopStack, $attributes),
            );
            $node['attrs'] = $attributes;
        }

        if (is_array($node['marks'] ?? null)) {
            foreach ($node['marks'] as $index => $mark) {
                if (!is_array($mark) || ($mark['type'] ?? null) !== 'twigInlineIf') {
                    continue;
                }

                if (!is_array($mark['attrs']['conditionAst'] ?? null)) {
                    throw new InvalidArgumentException(
                        'Twig inline conditions must contain a structured condition AST.',
                    );
                }

                $mark['attrs']['condition'] = $this->serializeCondition(
                    $mark['attrs']['conditionAst'],
                    $loopStack,
                );
                $node['marks'][$index] = $mark;
            }
        }

        $content = $node['content'] ?? null;

        if (!is_array($content)) {
            return $node;
        }

        $childLoopStack = $loopStack;

        if (in_array($node['type'] ?? null, ['tableRow', 'tableCell', 'tableHeader'], true)) {
            $childLoopStack = $this->appendAttributeLoop($childLoopStack, $attributes);
        }

        foreach ($content as $index => $child) {
            if (!is_array($child)) {
                continue;
            }

            $nestedLoopStack = $childLoopStack;

            if (($node['type'] ?? null) === 'twigFor') {
                $isBodyBranch = ($child['type'] ?? null) === 'twigForBody';

                if ($isBodyBranch) {
                    $nestedLoopStack[] = [
                        'item' => $attributes['item'] ?? null,
                        'key' => $attributes['key'] ?? null,
                        'iterable' => $attributes['iterable'] ?? null,
                    ];
                }
            }

            $content[$index] = $this->compileConditionNode($child, $nestedLoopStack);
        }

        $node['content'] = $content;

        return $node;
    }

    /** @return list<string> */
    private function getConditionRuleToolNames(): array
    {
        return array_map(
            fn (Condition $condition) => $this->getConditionToolName('insertTwigIf', $condition),
            array_values($this->conditions),
        );
    }

    /** @return list<RichEditorTool> */
    private function getConditionRuleTools(): array
    {
        $tools = [];

        foreach ($this->conditions as $condition) {
            $conditionType = Js::from($condition->getKey())->toHtml();

            $tools[] = RichEditorTool::make($this->getConditionToolName('insertTwigIf', $condition))
                ->label($condition->getLabel())
                ->icon(generate_icon_html(
                    $condition->getIcon() ?? match ($condition->getType()) {
                        ConditionKind::Truthy => 'heroicon-o-check-circle',
                        ConditionKind::Comparison => 'heroicon-o-arrows-right-left',
                        ConditionKind::Test => 'heroicon-o-beaker',
                    },
                    attributes: new FilamentComponentAttributeBag()
                        ->class(['fi-stylus-twig-metadata-icon'])
                        ->color(IconComponent::class, $condition->getColor()),
                ))
                ->extraAttributes([
                    'class' => 'fi-stylus-twig-condition-rule-option',
                    'data-stylus-twig-condition-description' => $condition->getDescription(),
                    'data-stylus-twig-color' => is_string($condition->getColor()) ? $condition->getColor() : null,
                ])
                ->action('insertTwigIf', <<<JS
                    {
                        conditionType: {$conditionType},
                        inline: window.PHPinnacleStylusTwigEditor?.canInsertTwigInlineIf(\$getEditor()) ?? false,
                        loopStack: window.PHPinnacleStylusTwigEditor?.getLoopStack(\$getEditor()) ?? [],
                    }
                    JS);

            $tools[] = RichEditorTool::make($this->getConditionToolName('insertTwigTableRowIf', $condition))
                ->label($condition->getLabel())
                ->icon(generate_icon_html(
                    $condition->getIcon() ?? match ($condition->getType()) {
                        ConditionKind::Truthy => 'heroicon-o-check-circle',
                        ConditionKind::Comparison => 'heroicon-o-arrows-right-left',
                        ConditionKind::Test => 'heroicon-o-beaker',
                    },
                    attributes: new FilamentComponentAttributeBag()
                        ->class(['fi-stylus-twig-metadata-icon'])
                        ->color(IconComponent::class, $condition->getColor()),
                ))
                ->extraAttributes([
                    'class' => 'fi-stylus-twig-condition-rule-option',
                    'data-stylus-twig-condition-description' => $condition->getDescription(),
                    'data-stylus-twig-color' => is_string($condition->getColor()) ? $condition->getColor() : null,
                ])
                ->action('insertTwigTableRowIf', <<<JS
                    {
                        conditionType: {$conditionType},
                        conditionTarget: 'row',
                        loopStack: window.PHPinnacleStylusTwigEditor?.getLoopStack(\$getEditor()) ?? [],
                    }
                    JS)
                ->activeJsExpression(
                    'window.PHPinnacleStylusTwigEditor?.canInsertTwigTableRowIf($getEditor()) ?? false',
                )
                ->disabledWhenNotActive()
                ->activeStyling(false);
        }

        return $tools;
    }

    private function getConditionToolName(string $baseName, Condition $condition): string
    {
        return $condition->getType() === ConditionKind::Truthy
            ? $baseName
            : "{$baseName}.{$condition->getKey()}";
    }

    /** @return list<string> */
    private function getTableRowConditionRuleToolNames(): array
    {
        return array_map(
            fn (Condition $condition) => $this->getConditionToolName('insertTwigTableRowIf', $condition),
            array_values($this->conditions),
        );
    }

    private function makeTruthyCondition(): Condition
    {
        return ConditionDescriptor::truthy()
            ->label(__('phpinnacle-stylus::forms.twig_editor.condition.truthy'))
            ->icon('heroicon-o-check-circle')
            ->description(__('phpinnacle-stylus::forms.twig_editor.condition.truthy_description'))
            ->color('success');
    }
}
