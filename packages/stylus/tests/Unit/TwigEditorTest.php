<?php

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\ViewErrorBag;
use Livewire\Component as LivewireComponent;
use PHPinnacle\Stylus\Condition;
use PHPinnacle\Stylus\Contracts\Condition as ConditionContract;
use PHPinnacle\Stylus\Contracts\Filter as FilterContract;
use PHPinnacle\Stylus\Contracts\Variable as VariableContract;
use PHPinnacle\Stylus\Enums\ConditionKind;
use PHPinnacle\Stylus\Enums\FilterOutput;
use PHPinnacle\Stylus\Filter;
use PHPinnacle\Stylus\Forms\TwigEditor;
use PHPinnacle\Stylus\Snippet;
use PHPinnacle\Stylus\Variable;
use PHPinnacle\Stylus\VariableScope;
use Tests\TestCase;

uses(TestCase::class);

function stylus_twig_editor(array $state = [], bool $disabled = false, ?Closure $configure = null): TwigEditor
{
    view()->share('errors', new ViewErrorBag);

    $livewire = new class extends LivewireComponent implements HasSchemas {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];
    };

    $livewire->setId('stylus-twig-editor-test');
    $livewire->setName('stylus-twig-editor-test');

    $editor = TwigEditor::make('template')->disabled($disabled);
    $configure?->__invoke($editor);

    $schema = Schema::make($livewire)
        ->statePath('data')
        ->components([$editor]);

    if ($state === []) {
        $schema->fill();
    } else {
        $schema->fill(['template' => $state]);
    }

    return $editor;
}

it('is a Filament field with a versioned empty document default', function () {
    $editor = stylus_twig_editor();

    expect($editor)
        ->toBeInstanceOf(RichEditor::class)
        ->and($editor->getState())
        ->toBe([
            'version' => 1,
            'document' => [
                'type' => 'doc',
                'content' => [
                    ['type' => 'paragraph'],
                ],
            ],
            'template' => '<p></p>',
        ]);
});

it('renders the asynchronous editor assets and entangled Filament state', function () {
    $html = stylus_twig_editor()->toHtml();

    expect($html)
        ->toContain('fi-fo-rich-editor')
        ->toContain('x-load')
        ->toContain('twig-rich-editor.js')
        ->toContain('richEditorFormComponent({')
        ->toContain('$wire.$entangle(')
        ->toContain('data.template')
        ->toContain('Heading 6')
        ->toContain('Insert condition')
        ->toContain('Template outline')
        ->toContain('Template structure')
        ->toContain('Condition branches')
        ->toContain('Both branches')
        ->toContain('If branch')
        ->toContain('Else branch')
        ->not->toContain('Insert comment')
        ->not->toContain('floatingToolbar::twigComment')->toContain(
            "x-bind:data-twig-structure-visible=\"twigTemplateStructureVisible ? 'true' : null\"",
        )->toContain('x-bind:data-twig-condition-branch-mode="twigConditionBranchMode"')->toContain(
            'twigTemplateStructureVisible = ! twigTemplateStructureVisible; twigStoreVariablePreferences()',
        )->toContain("twigSetConditionBranchMode('both')")->toContain("twigSetConditionBranchMode('if')")->toContain(
            "twigSetConditionBranchMode('else')",
        )->toContain('templateStructure: this.twigTemplateStructureVisible')->toContain(
            'conditionBranchMode: this.twigConditionBranchMode',
        )
        ->not->toContain('Insert loop')
        ->not->toContain('floatingToolbar::twigIf')->toContain(
            'fi-fo-rich-editor-panels fi-stylus-twig-inline-panels fi-stylus-twig-outline-panel',
        )->toContain('fi-fo-rich-editor-panels fi-stylus-twig-inline-panels fi-stylus-twig-filter-panel')->toContain(
            'fi-fo-rich-editor-panels fi-stylus-twig-inline-panels fi-stylus-twig-condition-panel',
        )->toContain('class="fi-stylus-twig-filter-item"')->toContain(
            'class="fi-stylus-twig-filter-item fi-stylus-twig-condition-rule"',
        )->toContain("twigConditionView: 'overview'")->toContain('twigSelectConditionOperandFilters(key)')->toContain(
            'twigEnsureConditionOperandFilterTarget()',
        )->toContain('twigConditionOperandTypes(key)')
        ->not->toContain('twigConditionAllowsNullLiteral()')
        ->not->toContain('twigNormalizeConditionRightOperandType()')
        ->not->toContain("valueType === 'null'")->toContain("text: 'string'")->toContain(
            "integer: 'number'",
        )->toContain('twigAvailableConditionOperandFilters()')->toContain(
            "'configureTwigConditionOperandFilter'",
        )->toContain('fi-stylus-twig-condition-operand')->toContain(
            'fi-stylus-twig-condition-filter-sections',
        )->toContain('x-on:change="twigSetConditionLiteral(key, $event.target.value === \'true\')"')->toContain(
            '<option value="true">True</option>',
        )->toContain('<option value="false">False</option>')
        ->not->toContain('fi-stylus-twig-condition-boolean')->toContain(
            "x-show=\"twigConditionView === 'rule'\" class=\"fi-stylus-twig-panel-actions\"",
        )
        ->not->toContain("twigConditionView === 'filters'")->toContain(
            'class="fi-stylus-twig-condition-rule-negate"',
        )->toContain('x-model="clause.negated"')->toContain(
            'x-bind:aria-pressed="twigConditionAst.negated"',
        )->toContain('x-bind:aria-pressed="twigConditionHasElse"')->toContain(
            'x-show="twigConditionTarget !== \'row\'"',
        )->toContain('x-bind:disabled="twigConditionAst.children.length <= 1"')->toContain(
            "twigConditionAst.operator = twigConditionAst.operator === 'and' ? 'or' : 'and'; twigPersistCondition()",
        )->toContain('x-text="twigConditionAst.operator.toUpperCase()"')->toContain('<span>NOT</span>')->toContain(
            '<span>ELSE</span>',
        )->toContain('twigConditionAst.negated = ! twigConditionAst.negated; twigPersistCondition()')->toContain(
            'twigConditionHasElse = ! twigConditionHasElse; twigPersistCondition()',
        )
        ->not->toContain('x-model="twigConditionAst.operator"')
        ->not->toContain('x-model="twigConditionAst.negated"')
        ->not->toContain('x-model="twigConditionHasElse"')->toContain(
            'twigLoopHasElse = ! twigLoopHasElse; twigPersistVariableFilters()',
        )->toContain('x-bind:aria-pressed="twigLoopHasElse"')
        ->not->toContain('x-model="twigLoopHasElse"')
        ->not->toContain('<code x-text="twigLoopIterable"></code>')
        ->not->toContain('fi-stylus-twig-loop-toggle')
        ->not->toContain('fi-stylus-twig-condition-configuration')->toContain(
            'x-on:pointerdown.prevent="twigStartPanelResize($event)"',
        )->toContain('--fi-stylus-twig-panel-width')->toContain('--fi-stylus-twig-panel-max-height')->toContain(
            'x-on:click.outside="twigClosePanelsOnOutsideClick($event)"',
        )->toContain('data-stylus-twig-panel-trigger')->toContain('twigTogglePanel')->toContain(
            'panelWidth: this.twigPanelWidth',
        )->toContain('Resize panel')->toContain('draggable="true"')
        ->not->toContain('fi-stylus-twig-filter-drag-handle')->toContain(
            'fi-stylus-twig-snippet-button fi-stylus-twig-outline-item',
        )
        ->not->toContain('fi-stylus-twig-editor-shell-open')->toContain('Loop settings')
        ->not->toContain('<dialog')
        ->not->toContain('twigEditorComponent({')
        ->not->toContain('twig-editor.css')->and(strpos(
            $html,
            'class="fi-stylus-twig-panel-actions"',
        ))->toBeLessThan(strpos($html, 'class="fi-stylus-twig-filter-panel-body"'));

    expect(substr_count($html, 'class="fi-stylus-twig-panel-resizer"'))->toBe(5);
    expect(substr_count($html, 'x-on:click.outside="twigClosePanelsOnOutsideClick($event)"'))->toBe(5);
    expect(substr_count($html, "'--fi-stylus-twig-panel-max-height'"))->toBe(5);
    expect(substr_count($html, 'fi-stylus-twig-panel-action-operator'))
        ->toBe(1)
        ->and(strpos($html, 'fi-stylus-twig-condition-panel'))
        ->toBeLessThan(strpos($html, 'fi-stylus-twig-panel-action-operator'));
    expect(substr_count($html, '<span>Keep content</span>'))
        ->toBe(0)
        ->and(substr_count($html, '<span>Delete</span>'))
        ->toBe(0)
        ->and(substr_count($html, '<span>ELSE</span>'))
        ->toBe(2)
        ->and(substr_count($html, 'fi-stylus-twig-panel-action-icon'))
        ->toBe(6);
});

it('shows typed and grouped variables in its own panel', function () {
    $editor = stylus_twig_editor()
        ->variables(
            Variable::text('foo')->label('My var')->sample('Example value'),
            Variable::make('order.total', 'number')->label('Order total')->group('Order'),
            Variable::collection('orders')->items(Variable::make('order', 'order')),
        )
        ->filters(
            Filter::make('upper')->types('text')->blocks(),
            Filter::make('number_format')
                ->label('Format number')
                ->types('number')
                ->schema([TextInput::make('decimals')]),
            Filter::make('sort')->types('collection'),
            Filter::make('first')
                ->types('collection')
                ->output(FilterOutput::CollectionItem),
        );

    expect($editor->getGroupedVariables())
        ->toHaveKeys(['', 'Order'])
        ->and($editor->getFilterOptionsForVariable('foo'))
        ->toBe(['upper' => 'Upper'])
        ->and($editor->getFilterOptionsForVariable('order.total'))
        ->toBe(['number_format' => 'Format number'])
        ->and($editor->getFilterOptionsForVariable('orders'))
        ->toBe(['sort' => 'Sort', 'first' => 'First'])
        ->and($editor->getCollectionFilters())
        ->toHaveKey('sort')
        ->and($editor->getCollectionFilters())
        ->not->toHaveKeys(['upper', 'number_format', 'first'])->and(
            collect($editor->getFilterDefinitionsForBrowser())->keyBy('name')->get('upper'),
        )->toHaveKeys([
            'name',
            'label',
            'description',
            'types',
            'configurable',
            'collectionCompatible',
            'blockCompatible',
            'conditionCompatible',
            'color',
            'iconHtml',
        ])->and(
            collect($editor->getFilterDefinitionsForBrowser())->keyBy('name')->get('upper')['configurable'],
        )->toBeFalse()->and(
            collect($editor->getFilterDefinitionsForBrowser())->keyBy('name')->get('number_format')['configurable'],
        )->toBeTrue()->and(
            collect($editor->getFilterDefinitionsForBrowser())->keyBy('name')->get('sort')['collectionCompatible'],
        )->toBeTrue()->and(
            collect($editor->getFilterDefinitionsForBrowser())->keyBy('name')->get('first')['collectionCompatible'],
        )->toBeFalse()->and(
            collect($editor->getFilterDefinitionsForBrowser())->keyBy('name')->get('upper')['blockCompatible'],
        )->toBeTrue()->and(
            collect($editor->getFilterDefinitionsForBrowser())->keyBy('name')->get('sort')['blockCompatible'],
        )->toBeFalse()->and(
            collect($editor->getFilterDefinitionsForBrowser())->keyBy('name')->get('upper')['conditionCompatible'],
        )->toBeTrue()->and(
            collect($editor->getFilterDefinitionsForBrowser())->keyBy('name')->get('first')['conditionCompatible'],
        )->toBeFalse()->and($editor->getMergeTags())->toBe([])->and($editor->hasToolbarButton(
            'twigVariables',
        ))->toBeTrue()->and($editor->hasToolbarButton(
            'twigTemplateStructure',
        ))->toBeTrue()->and($editor->hasToolbarButton('insertTwigIf'))->toBeTrue()->and($editor->hasToolbarButton(
            'insertTwigInlineIf',
        ))->toBeFalse()->and($editor->hasToolbarButton('insertTwigFor'))->toBeFalse()->and($editor->hasToolbarButton(
            'mergeTags',
        ))->toBeFalse()->and($editor->toHtml())->toContain('fi-stylus-twig-variable-panel')->toContain(
            'x-teleport="[data-stylus-twig-editor=',
        )->toContain('fi-fo-rich-editor-panels fi-stylus-twig-inline-panels fi-stylus-twig-variable-panel')->toContain(
            'insertVariable($root, variable)',
        )->toContain('twigDisplayedVariableGroups()')->toContain('twigIsVariableGroup(variable)')->toContain(
            'twigToggleVariableGroup(variable)',
        )->toContain('<template x-if="group.label">')->toContain('x-bind:aria-expanded="variable.expanded"')->toContain(
            'class="fi-stylus-twig-variable-group-chevron"',
        )
        ->not->toContain('fi-stylus-twig-variable-group-controls')->toContain(
            'fi-stylus-twig-variable-group-count',
        )->toContain('twigToggleFavorite(variable)')->toContain('Search variables')->toContain(
            'Show sample values',
        )->toContain('Example value')->toContain('phpinnacle-stylus.twig-editor.data.template')->toContain(
            'My var',
        )->toContain('Order total')->toContain('Order')->toContain('twigClearRecentVariables()')->toContain(
            'Clear recent',
        )
        ->not->toContain('Length, last, and reverse indexes require a countable iterable.')->toContain(
            'twigOpenVariableFilterPanel(variable)',
        )->toContain('twigOpenLoopSettingsPanel(loop)')->toContain('twigOpenTableLoopPanel(loop)')->toContain(
            'twigAvailableLoopIterables()',
        )->toContain('twigInsertTableLoop(variable, $wire)')->toContain(
            'twigOpenApplyInsertPanel(editorSelection)',
        )->toContain('twigOpenApplySettingsPanel(apply)')->toContain(
            "twigFilterPanelTarget === 'apply-insert'",
        )->toContain('twigDropVariableFilter(index)')->toContain('twigDeletePanelTarget()')->toContain(
            'twigKeepLoopContent()',
        )->toContain('twigKeepApplyContent()')->toContain(
            "twigFilterPanelTarget === 'apply' && twigVariableFilters.length <= 1",
        )->toContain('<template x-if="filter.configurable">')->toContain(
            '<template x-if="! filter.configurable">',
        )->toContain('Delete variable')->toContain('Keep loop content')->toContain('Stop repeating row')->toContain(
            'Stop repeating cells',
        )->toContain('Delete loop')->toContain('Keep filtered content')->toContain('Delete filtered content')
        ->not->toContain('floatingToolbar::twigVariable')
        ->not->toContain('floatingToolbar::twigFor')
        ->not->toContain('floatingToolbar::twigApply')->toContain("'configureTwigVariableFilter'")->toContain(
            "'configureTwigLoopFilter'",
        )->toContain("'configureTwigApplyFilter'")->toContain('updateVariableFilters(')->toContain(
            'updateLoopSettings(',
        )->toContain('updateTableLoopSettings(')->toContain('keepTableLoopContent(')->toContain(
            "twigLoopTarget === 'block'",
        )->toContain('updateApplyFilters(')->toContain('Filter pipeline')->toContain('Available filters');
});

it('exposes Filament presentation metadata for Twig editor descriptors', function () {
    $variable = Variable::text('user.name')
        ->label('Customer name')
        ->icon(Heroicon::User)
        ->description('The customer display name.')
        ->color('info')
        ->sample('Ada Lovelace');
    $filter = Filter::make('upper')
        ->label('Uppercase')
        ->icon(Heroicon::BarsArrowUp)
        ->description('Convert text to uppercase.')
        ->color('warning')
        ->types('text')
        ->blocks();
    $snippet = Snippet::make('signature', [['type' => 'paragraph']])
        ->label('Signature')
        ->icon(Heroicon::DocumentText)
        ->description('A reusable closing line.')
        ->color('success')
        ->requires('user.name');
    $condition = Condition::comparison('equals', '==')
        ->label('Equals')
        ->icon(Heroicon::Scale)
        ->description('Compare two values for equality.')
        ->color('info');
    $editorHtml = stylus_twig_editor()
        ->variables($variable)
        ->snippets($snippet)
        ->toHtml();

    foreach ([$variable, $filter, $condition, $snippet] as $descriptor) {
        expect($descriptor)
            ->toBeInstanceOf(HasLabel::class)
            ->toBeInstanceOf(HasIcon::class)
            ->toBeInstanceOf(HasDescription::class)
            ->toBeInstanceOf(HasColor::class);
    }

    expect($variable)
        ->toBeInstanceOf(VariableContract::class)
        ->and($filter)
        ->toBeInstanceOf(FilterContract::class)
        ->and($condition)
        ->toBeInstanceOf(ConditionContract::class)
        ->and((string) new ReflectionMethod(TwigEditor::class, 'variables')->getParameters()[0]->getType())
        ->toBe(VariableContract::class)
        ->and((string) new ReflectionMethod(TwigEditor::class, 'filters')->getParameters()[0]->getType())
        ->toBe(FilterContract::class)
        ->and((string) new ReflectionMethod(TwigEditor::class, 'conditions')->getParameters()[0]->getType())
        ->toBe(ConditionContract::class)
        ->and((string) new ReflectionMethod(Variable::class, 'properties')->getParameters()[0]->getType())
        ->toBe(VariableContract::class)
        ->and((string) new ReflectionMethod(Variable::class, 'items')->getParameters()[0]->getType())
        ->toBe(VariableContract::class)
        ->and($variable->getName())
        ->toBe('user.name')
        ->and($variable->getType())
        ->toBe('text')
        ->and($variable->getLabel())
        ->toBe('Customer name')
        ->and($variable->getIcon())
        ->toBe(Heroicon::User)
        ->and($variable->getDescription())
        ->toBe('The customer display name.')
        ->and($variable->getColor())
        ->toBe('info')
        ->and($variable->toArray()['iconHtml'])
        ->toContain('fi-icon')
        ->and($filter->getName())
        ->toBe('upper')
        ->and($filter->getTypes())
        ->toBe(['text'])
        ->and($filter->supportsBlocks())
        ->toBeTrue()
        ->and($filter->getLabel())
        ->toBe('Uppercase')
        ->and($filter->getIcon())
        ->toBe(Heroicon::BarsArrowUp)
        ->and($filter->getDescription())
        ->toBe('Convert text to uppercase.')
        ->and($filter->getColor())
        ->toBe('warning')
        ->and($condition->getName())
        ->toBe('equals')
        ->and($condition->getExpression())
        ->toBe('==')
        ->and($condition->getType())
        ->toBe(ConditionKind::Comparison)
        ->and($condition->matchesVariableTypes())
        ->toBeTrue()
        ->and($condition->getLabel())
        ->toBe('Equals')
        ->and($condition->getIcon())
        ->toBe(Heroicon::Scale)
        ->and($condition->getDescription())
        ->toBe('Compare two values for equality.')
        ->and($condition->getColor())
        ->toBe('info')
        ->and($snippet->getLabel())
        ->toBe('Signature')
        ->and($snippet->getIcon())
        ->toBe(Heroicon::DocumentText)
        ->and($snippet->getDescription())
        ->toBe('A reusable closing line.')
        ->and($snippet->getColor())
        ->toBe('success')
        ->and($snippet->toArray()['iconHtml'])
        ->toContain('fi-icon')
        ->and($editorHtml)
        ->toContain('The customer display name.')
        ->toContain('A reusable closing line.')
        ->toContain('fi-stylus-twig-metadata-icon');
});

it('serializes named type and snippet requirements as lists', function () {
    $condition = Condition::comparison('numeric', '==')->types(first: 'number', second: 'integer');
    $filter = Filter::make('numeric')->types(first: 'number', second: 'integer');
    $snippet = Snippet::make('signature', [])->requires(first: 'user.name', second: 'user.email');
    $editor = TwigEditor::make('template')->conditions($condition)->filters($filter);

    $conditions = collect($editor->getConditionDefinitionsForBrowser())->keyBy('name');
    $filters = collect($editor->getFilterDefinitionsForBrowser())->keyBy('name');

    expect(json_encode($conditions['numeric']['types']))
        ->toBe('["number","integer"]')
        ->and(json_encode($filters['numeric']['types']))
        ->toBe('["number","integer"]')
        ->and(json_encode($snippet->toArray()['requiredVariables']))
        ->toBe('["user.name","user.email"]');
});

it('shows reusable snippets in its own panel instead of a modal action', function () {
    $editor = stylus_twig_editor()
        ->variables(Variable::text('user.name'))
        ->snippets(
            Snippet::make('account_signature', [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'Kind regards']],
            ]])
                ->label('Account signature')
                ->requires('user.name'),
        );
    $snippetTool = $editor->getTools()['insertTwigSnippet'];

    expect($snippetTool->getJsHandler())
        ->toBe("twigTogglePanel('snippets')")
        ->not->toContain('$wire.mountAction')->and($snippetTool->getActiveJsExpression())->toBe(
            'twigSnippetPanelOpen',
        )->and(array_keys($editor->getActions()))
        ->not->toContain('insertTwigSnippet')->and($editor->toHtml())->toContain(
            'fi-fo-rich-editor-panels fi-stylus-twig-inline-panels fi-stylus-twig-snippet-panel',
        )->toContain('twigInsertSnippet(snippet)')->toContain('Search snippets')->toContain(
            'Account signature',
        )->toContain('account_signature')->toContain('user.name');
});

it('derives typed variables from nested lexical loop scopes', function () {
    $editor = stylus_twig_editor()
        ->variables(
            Variable::collection('orders')
                ->label('Orders')
                ->items(
                    Variable::make('order', 'order')->properties(
                        Variable::text('number')->label('Order number'),
                        Variable::collection('lines')->items(
                            Variable::make('line', 'line')->properties(
                                Variable::text('description'),
                                Variable::make('amount', 'number'),
                            ),
                            keyType: 'integer',
                        ),
                    ),
                    keyType: 'text',
                ),
        )
        ->filters(
            Filter::make('upper')->types('text'),
            Filter::make('number_format')->types('number'),
        );

    $orderLoop = [[
        'item' => 'order',
        'key' => 'orderKey',
        'iterable' => 'orders',
    ]];
    $nestedLoop = [
        ...$orderLoop,
        [
            'item' => 'line',
            'key' => 'lineIndex',
            'iterable' => 'order.lines',
        ],
    ];
    $thirdLoop = [
        ...$nestedLoop,
        [
            'item' => 'nestedLine',
            'key' => null,
            'iterable' => 'order.lines',
        ],
    ];
    expect($editor->getVariables())
        ->toHaveKeys(['orders'])
        ->and($editor->getIterableOptions())
        ->toHaveKey('orders')
        ->and($editor->getVariable('order.number', $orderLoop)?->type)
        ->toBe('text')
        ->and($editor->getVariable('order.lines', $orderLoop)?->isCollection())
        ->toBeTrue()
        ->and($editor->getVariable('orderKey', $orderLoop)?->type)
        ->toBe('text')
        ->and($editor->getVariable('loop.index', $orderLoop)?->type)
        ->toBe('integer')
        ->and($editor->getVariable('loop.parent', $orderLoop))
        ->toBeNull()
        ->and($editor->getIterableOptions($orderLoop))
        ->toHaveKey('order.lines')
        ->and($editor->getVariable('order.number', $nestedLoop)?->type)
        ->toBe('text')
        ->and($editor->getVariable('line.description', $nestedLoop)?->type)
        ->toBe('text')
        ->and($editor->getVariable('line.amount', $nestedLoop)?->type)
        ->toBe('number')
        ->and($editor->getVariable('lineIndex', $nestedLoop)?->type)
        ->toBe('integer')
        ->and($editor->getVariable('loop.parent.loop.index', $nestedLoop)?->type)
        ->toBe('integer')
        ->and($editor->getVariable('loop.parent.loop.parent', $nestedLoop))
        ->toBeNull()
        ->and($editor->getVariable('loop.parent.loop.parent.loop.index', $thirdLoop)?->type)
        ->toBe('integer')
        ->and($editor->getFilterOptionsForVariable('line.description', $nestedLoop))
        ->toBe(['upper' => 'Upper'])
        ->and($editor->getFilterOptionsForVariable('line.amount', $nestedLoop))
        ->toBe(['number_format' => 'Number Format'])
        ->and($editor->getVariable('line.description', $orderLoop))
        ->toBeNull();
});

it('initializes browser variables from the root scope', function () {
    $editor = stylus_twig_editor()
        ->variables(
            Variable::collection('orders')->items(
                Variable::make('order', 'order')->properties(
                    Variable::text('number'),
                ),
            ),
        );
    $variableNames = collect($editor->getVariableGroupsForBrowser())
        ->flatMap(static fn (array $group) => $group['variables'])
        ->pluck('name')
        ->all();

    expect($variableNames)->toBe(['orders']);
});

it('flattens object properties and serializes collection definitions for the browser', function () {
    $editor = stylus_twig_editor()
        ->variables(
            Variable::make('user', 'user')
                ->group('Customer')
                ->properties(
                    Variable::text('name')->sample('Ada Lovelace'),
                    Variable::make('address', 'address')->properties(
                        Variable::text('city'),
                    ),
                ),
            Variable::collection('orders')->items(Variable::make('order', 'order')),
        );

    expect($editor->getVariables())
        ->toHaveKeys(['user', 'user.name', 'user.address', 'user.address.city', 'orders'])
        ->and($editor->getGroupedVariables()['Customer'])
        ->toHaveCount(4)
        ->and($editor->getVariableDefinitions()[1]['name'])
        ->toBe('orders')
        ->and($editor->getVariableDefinitions()[1]['type'])
        ->toBe('collection')
        ->and($editor->getVariableDefinitions()[1]['item']['name'])
        ->toBe('order')
        ->and($editor->getVariableDefinitions()[1]['item']['type'])
        ->toBe('order')
        ->and($editor->getVariableDefinitions()[0]['properties'][0]['sample'])
        ->toBe('Ada Lovelace')
        ->and($editor->toHtml())
        ->toContain('twigVariableDefinitions')
        ->toContain('insertVariable($root, variable)')
        ->toContain('\\u0022item\\u0022:{\\u0022name\\u0022:\\u0022order\\u0022');
});

it('builds filter arguments from its configuration schema state', function () {
    $filter = Filter::make('date')
        ->types('date')
        ->schema([TextInput::make('format')]);
    $customFilter = Filter::make('custom')->argumentsUsing(
        static fn (array $configuration) => [(string) $configuration['expression']],
    );
    $blockFilter = Filter::make('upper')->types('text')->blocks();

    expect($filter->supports('date'))
        ->toBeTrue()
        ->and($filter->supports('text'))
        ->toBeFalse()
        ->and($customFilter->supports('date'))
        ->toBeTrue()
        ->and($customFilter->supports('collection'))
        ->toBeTrue()
        ->and($filter->getArguments(['format' => "'Y-m-d'"]))
        ->toBe(["'Y-m-d'"])
        ->and($filter->getSchema()[0])
        ->not
        ->toBe($filter->schema[0])
        ->and($customFilter->getArguments(['expression' => 'user.locale']))
        ->toBe(['user.locale'])
        ->and($blockFilter->supports('text'))
        ->toBeTrue()
        ->and($blockFilter->supportsBlocks)
        ->toBeTrue();
});

it('matches conditions and variable options by type while empty type lists support all types', function () {
    $numericCondition = Condition::comparison('greater_than', '>')->types('number', 'integer');
    $untypedCondition = Condition::test('defined');
    $untypedComparison = Condition::comparison('equals', '==');
    $scope = new VariableScope([
        'name' => Variable::text('name'),
        'amount' => Variable::make('amount', 'number'),
        'active' => Variable::make('active', 'boolean'),
    ]);

    expect($numericCondition->supports('number'))
        ->toBeTrue()
        ->and($numericCondition->supports('boolean'))
        ->toBeFalse()
        ->and($untypedCondition->supports('boolean'))
        ->toBeTrue()
        ->and($untypedCondition->supports('custom'))
        ->toBeTrue()
        ->and($untypedComparison->supports('custom'))
        ->toBeTrue()
        ->and($untypedComparison->matchesVariableTypes)
        ->toBeTrue()
        ->and($scope->getVariableOptions(['boolean']))
        ->toHaveKey('active')
        ->not
        ->toHaveKeys(['name', 'amount'])
        ->and($scope->getVariableOptions())
        ->toHaveKeys(['name', 'amount', 'active']);
});

it('renders a disabled editor as non-editable', function () {
    expect(stylus_twig_editor(disabled: true)->toHtml())
        ->toContain('isDisabled: true');
});

it('normalizes raw documents and regenerates the dehydrated Twig template', function () {
    $editor = stylus_twig_editor([
        'type' => 'doc',
        'content' => [[
            'type' => 'paragraph',
            'content' => [[
                'type' => 'twigVariable',
                'attrs' => [
                    'expression' => 'user.name',
                    'filters' => [['name' => 'upper', 'arguments' => []]],
                ],
            ]],
        ]],
    ]);

    expect($editor->getRawState())
        ->toHaveKey('type', 'doc')
        ->and($editor->getState())
        ->toMatchArray([
            'version' => 1,
            'template' => '<p>{{ user.name|upper }}</p>',
        ]);
});

it('registers Twig tools and Filament configuration actions', function () {
    $editor = stylus_twig_editor(configure: fn (TwigEditor $editor) => $editor->conditions(
        Condition::comparison('equals', '==')->description('Compare values.'),
        Condition::test('defined')->description('Check whether a value exists.'),
    ));
    $insertConditionTool = $editor->getTools()['insertTwigIf'];
    $insertEqualsConditionTool = $editor->getTools()['insertTwigIf.comparison:equals'];
    $insertRowConditionTool = $editor->getTools()['insertTwigTableRowIf.comparison:equals'];
    $insertApplyTool = $editor->getTools()['insertTwigApply'];
    $conditionViewBothTool = $editor->getTools()['twigConditionViewBoth'];
    $conditionViewIfTool = $editor->getTools()['twigConditionViewIf'];
    $conditionViewElseTool = $editor->getTools()['twigConditionViewElse'];
    $repeatRowTool = $editor->getTools()['configureTwigTableRowLoop'];
    $repeatCellsTool = $editor->getTools()['configureTwigTableCellLoop'];

    expect(array_keys($editor->getTools()))
        ->toContain(
            'twigVariables',
            'twigTemplateStructure',
            'twigOutline',
            'twigConditionViewBoth',
            'twigConditionViewIf',
            'twigConditionViewElse',
            'insertTwigApply',
            'insertTwigSnippet',
            'insertTwigIf',
            'insertTwigIf.comparison:equals',
            'insertTwigIf.test:defined',
            'insertTwigTableRowIf',
            'insertTwigTableRowIf.comparison:equals',
            'insertTwigTableRowIf.test:defined',
            'insertTwigFor',
            'configureTwigTableRowLoop',
            'configureTwigTableCellLoop',
        )
        ->and(array_keys($editor->getActions()))
        ->toContain(
            'configureTwigVariableFilter',
            'configureTwigLoopFilter',
            'configureTwigApplyFilter',
            'configureTwigConditionOperandFilter',
            'insertTwigApply',
            'insertTwigIf',
            'insertTwigTableRowIf',
            'insertTwigFor',
            'configureTwigTableRowLoop',
            'configureTwigTableCellLoop',
        )
        ->not->toContain('insertTwigVariable', 'insertTwigSnippet')->and(array_keys($editor->getTools()))
        ->not->toContain(
            'insertTwigComment',
            'editTwigComment',
            'deleteTwigComment',
            'editTwigIf',
            'editTwigInlineIf',
            'editTwigVariable',
            'deleteTwigVariable',
            'editTwigFor',
            'unwrapTwigFor',
            'deleteTwigFor',
            'editTwigApply',
            'unwrapTwigApply',
            'deleteTwigApply',
            'removeTwigTableRowLoop',
            'removeTwigTableCellLoop',
        )->and(array_keys($editor->getActions()))
        ->not->toContain(
            'insertTwigComment',
            'editTwigComment',
            'configureTwigConditionRule',
            'editTwigIf',
            'editTwigInlineIf',
        )->and($insertConditionTool->getJsHandler())->toContain("\$wire.mountAction('insertTwigIf'")->toContain(
            "conditionType: 'truthy'",
        )->toContain('canInsertTwigInlineIf($getEditor())')->toContain(
            'getLoopStack($getEditor())',
        )->and($insertConditionTool->getExtraAttributeBag()->get('data-stylus-twig-condition-description'))->toBe(
            'The value must evaluate to true.',
        )->and($insertEqualsConditionTool->getJsHandler())->toContain("\$wire.mountAction('insertTwigIf'")->toContain(
            "conditionType: 'comparison:equals'",
        )->and($insertEqualsConditionTool->getExtraAttributeBag()->get('data-stylus-twig-condition-description'))->toBe(
            'Compare values.',
        )->and($insertRowConditionTool->getJsHandler())->toContain(
            "\$wire.mountAction('insertTwigTableRowIf'",
        )->toContain("conditionTarget: 'row'")->and($insertApplyTool->getJsHandler())->toContain(
            'twigOpenApplyInsertPanel($getEditor()?.state.selection.toJSON() ?? null)',
        )->and($insertApplyTool->getExtraAttributeBag()->get('data-stylus-twig-panel-trigger'))->toBe('apply')->and(
            $conditionViewBothTool->getJsHandler(),
        )->toBe("twigSetConditionBranchMode('both')")->and($conditionViewBothTool->getActiveJsExpression())->toBe(
            "twigConditionBranchMode === 'both'",
        )->and($conditionViewIfTool->getJsHandler())->toBe("twigSetConditionBranchMode('if')")->and(
            $conditionViewIfTool->getActiveJsExpression(),
        )->toBe("twigConditionBranchMode === 'if'")->and($conditionViewElseTool->getJsHandler())->toBe(
            "twigSetConditionBranchMode('else')",
        )->and($conditionViewElseTool->getActiveJsExpression())->toBe("twigConditionBranchMode === 'else'")->and(
            $repeatRowTool->getJsHandler(),
        )->toContain(
            'twigOpenTableLoopPanel(window.PHPinnacleStylusTwigEditor?.getTableRowLoopArguments($getEditor()) ?? null)',
        )->and($repeatRowTool->getExtraAttributeBag()->get('data-stylus-twig-panel-trigger'))->toBe('row-loop')->and(
            $repeatRowTool->getActiveJsExpression(),
        )->toContain('canConfigureTwigTableRowLoop($getEditor())')->toContain('hasTwigTableRowLoop($getEditor())')->and(
            $repeatCellsTool->getJsHandler(),
        )->toContain(
            'twigOpenTableLoopPanel(window.PHPinnacleStylusTwigEditor?.getTableCellLoopArguments($getEditor()) ?? null)',
        )->and($repeatCellsTool->getExtraAttributeBag()->get('data-stylus-twig-panel-trigger'))->toBe('cell-loop')->and(
            $repeatCellsTool->getActiveJsExpression(),
        )->toContain('canConfigureTwigTableCellLoop($getEditor())')->toContain(
            'hasTwigTableCellLoop($getEditor())',
        )->and($editor->getActions()['configureTwigTableRowLoop']->hasModal())->toBeFalse()->and(
            $editor->getActions()['configureTwigTableCellLoop']->hasModal(),
        )->toBeFalse();
});

it('includes every registered condition in the table row condition toolbar', function () {
    $editor = stylus_twig_editor(configure: fn (TwigEditor $editor) => $editor->conditions(
        Condition::comparison('equals', '=='),
        Condition::test('defined'),
    ));

    $conditionGroup = collect($editor->getFloatingToolbars()['table'])
        ->first(static fn (mixed $tool) => $tool instanceof ToolbarButtonGroup);

    expect($conditionGroup)
        ->toBeInstanceOf(ToolbarButtonGroup::class)
        ->and($conditionGroup->getButtons())
        ->toBe([
            'insertTwigTableRowIf',
            'insertTwigTableRowIf.comparison:equals',
            'insertTwigTableRowIf.test:defined',
        ])
        ->and($conditionGroup->getExtraAttributeBag()->get('data-stylus-twig-panel-trigger'))
        ->toBe('row-condition')
        ->and($conditionGroup->getExtraAttributeBag()->get('x-on:click'))
        ->toContain('hasTwigTableRowCondition($getEditor())')
        ->toContain('openTwigTableRowCondition($getEditor())')
        ->toContain('open = ! open');
});

it('uses descriptor metadata in focused filter and condition rule modals', function () {
    $editor = stylus_twig_editor()
        ->filters(
            Filter::make('strip_html')
                ->icon(Heroicon::CodeBracket)
                ->description('Remove HTML tags from the value.')
                ->color('warning')
                ->schema([TextInput::make('allowed_tags')]),
        )
        ->conditions(
            Condition::comparison('equals', '==')
                ->icon(Heroicon::Scale)
                ->description('The first value must equal the second.')
                ->color('info'),
        );

    foreach ([
        'configureTwigVariableFilter',
        'configureTwigConditionOperandFilter',
        'configureTwigLoopFilter',
        'configureTwigApplyFilter',
        'insertTwigApply',
    ] as $actionName) {
        $action = $editor->getActions()[$actionName]->arguments(['filterName' => 'strip_html']);

        expect($action->getModalIcon())
            ->toBe(Heroicon::CodeBracket)
            ->and($action->getModalIconColor())
            ->toBe('warning')
            ->and($action->getModalDescription())
            ->toBe('Remove HTML tags from the value.');
    }

    foreach (['insertTwigIf', 'insertTwigTableRowIf'] as $actionName) {
        $action = $editor->getActions()[$actionName]->arguments(['conditionType' => 'comparison:equals']);

        expect($action->getModalIcon())
            ->toBe(Heroicon::Scale)
            ->and($action->getModalIconColor())
            ->toBe('info')
            ->and($action->getModalDescription())
            ->toBe('The first value must equal the second.');
    }

    $truthyAction = $editor->getActions()['insertTwigIf']->arguments(['conditionType' => 'truthy']);

    expect($truthyAction->getModalIcon())
        ->toBe('heroicon-o-check-circle')
        ->and($truthyAction->getModalIconColor())
        ->toBe('success')
        ->and($truthyAction->getModalDescription())
        ->toBe('The value must evaluate to true.');
});

it('uses a boolean select for condition literals', function () {
    $editor = stylus_twig_editor(configure: fn (TwigEditor $editor) => $editor
        ->variables(Variable::make('payment.transfer_enabled', 'boolean'))
        ->conditions(Condition::comparison('equals', '==')->types('boolean')));
    $action = $editor->getActions()['insertTwigIf']->arguments([
        'conditionType' => 'comparison:equals',
    ]);
    $actionSchema = $action->getSchema(
        Schema::make($editor->getLivewire())->statePath('mountedActions.0.data'),
    );
    $booleanValue = collect($actionSchema?->getFlatComponents(withHidden: true))
        ->first(
            static fn (mixed $component) => $component instanceof Select && $component->getName() === 'right_boolean',
        );

    expect($booleanValue)
        ->toBeInstanceOf(Select::class)
        ->and($booleanValue->getOptions())
        ->toBe([
            1 => 'True',
            0 => 'False',
        ])
        ->and($booleanValue->isRequired())
        ->toBeTrue()
        ->and($booleanValue->canSelectPlaceholder())
        ->toBeFalse();
});

it('exposes condition definitions to the condition settings panel', function () {
    $editor = stylus_twig_editor()
        ->conditions(
            Condition::comparison('equals', '==')
                ->description('Compare values.')
                ->color('warning'),
            Condition::test('defined'),
        );

    $definitions = $editor->getConditionDefinitionsForBrowser();

    expect($definitions)
        ->toHaveCount(3)
        ->and($definitions[0])
        ->toMatchArray([
            'key' => 'truthy',
            'type' => 'truthy',
            'label' => 'True',
            'description' => 'The value must evaluate to true.',
            'color' => 'success',
            'types' => ['boolean'],
            'matchVariableTypes' => false,
        ])
        ->and($definitions[0]['iconHtml'])
        ->toContain('fi-color-success')
        ->and($definitions[1])
        ->toMatchArray([
            'key' => 'comparison:equals',
            'type' => 'comparison',
            'name' => 'equals',
            'expression' => '==',
            'description' => 'Compare values.',
            'color' => 'warning',
            'types' => [],
            'matchVariableTypes' => true,
        ])
        ->and($definitions[1]['iconHtml'])
        ->toContain('fi-color-warning')
        ->and($definitions[2])
        ->toMatchArray([
            'key' => 'test:defined',
            'type' => 'test',
            'name' => 'defined',
            'expression' => 'defined',
        ]);
});

it('drives truthy behavior through the condition contract', function () {
    $truthy = $this->createStub(ConditionContract::class);
    $truthy->method('getName')->willReturn('truthy');
    $truthy->method('getExpression')->willReturn('');
    $truthy->method('getType')->willReturn(ConditionKind::Truthy);
    $truthy->method('getTypes')->willReturn(['text']);
    $truthy->method('matchesVariableTypes')->willReturn(false);
    $truthy->method('getKey')->willReturn('truthy');
    $truthy->method('getLabel')->willReturn('Has text');
    $truthy->method('getIcon')->willReturn('heroicon-o-document-text');
    $truthy->method('getDescription')->willReturn('The text must not be empty.');
    $truthy->method('getColor')->willReturn('info');
    $editor = stylus_twig_editor()
        ->variables(Variable::text('message'))
        ->conditions($truthy);
    $definition = $editor->getConditionDefinitionsForBrowser()[0];

    expect($editor->getCondition('truthy'))
        ->toBe($truthy)
        ->and($definition)
        ->toMatchArray([
            'key' => 'truthy',
            'type' => 'truthy',
            'label' => 'Has text',
            'description' => 'The text must not be empty.',
            'color' => 'info',
            'types' => ['text'],
        ])
        ->and($editor->getTools()['insertTwigIf']->getLabel())
        ->toBe('Has text')
        ->and($editor->serializeCondition([
            'type' => 'truthy',
            'subject' => ['type' => 'variable', 'name' => 'message'],
        ]))
        ->toBe('message');
});

it('drives filter capabilities through the filter contract', function () {
    $filter = $this->createStub(FilterContract::class);
    $filter->method('getName')->willReturn('normalize');
    $filter->method('getTypes')->willReturn(['text']);
    $filter->method('getOutput')->willReturn(FilterOutput::Same);
    $filter->method('supportsBlocks')->willReturn(true);
    $filter->method('getSchema')->willReturn([]);
    $filter->method('getLabel')->willReturn('Normalize');
    $filter->method('getIcon')->willReturn('heroicon-o-adjustments-horizontal');
    $filter->method('getDescription')->willReturn('Normalize text.');
    $filter->method('getColor')->willReturn('warning');
    $filter
        ->method('supports')
        ->willReturnCallback(
            static fn (string $variableType) => $variableType === 'text',
        );
    $editor = stylus_twig_editor()
        ->variables(Variable::text('message'))
        ->filters($filter);
    $definition = $editor->getFilterDefinitionsForBrowser()[0];

    expect($editor->getFilter('normalize'))
        ->toBe($filter)
        ->and($editor->getFilterOptionsForVariable('message'))
        ->toBe(['normalize' => 'Normalize'])
        ->and($definition)
        ->toMatchArray([
            'name' => 'normalize',
            'label' => 'Normalize',
            'description' => 'Normalize text.',
            'color' => 'warning',
            'types' => ['text'],
            'configurable' => false,
            'blockCompatible' => true,
            'conditionCompatible' => true,
        ]);
});

it('registers conditions and compiles persisted structured conditions', function () {
    $editor = stylus_twig_editor()
        ->variables(Variable::make('user.active', 'boolean'))
        ->conditions(
            Condition::comparison('equals', '=='),
            Condition::test('defined'),
        );
    $conditionAst = [
        'type' => 'group',
        'operator' => 'or',
        'negated' => false,
        'children' => [
            [
                'type' => 'truthy',
                'subject' => ['type' => 'variable', 'name' => 'user.active'],
                'negated' => false,
            ],
            [
                'type' => 'test',
                'subject' => ['type' => 'variable', 'name' => 'user.active'],
                'test' => 'defined',
                'negated' => false,
            ],
        ],
    ];
    $document = $editor->compileStructuredConditions([
        'type' => 'doc',
        'content' => [
            [
                'type' => 'twigIf',
                'attrs' => [
                    'condition' => 'tampered',
                    'conditionAst' => $conditionAst,
                ],
                'content' => [[
                    'type' => 'twigThen',
                    'content' => [['type' => 'paragraph']],
                ]],
            ],
            [
                'type' => 'paragraph',
                'content' => [[
                    'type' => 'text',
                    'text' => 'Active',
                    'marks' => [[
                        'type' => 'twigInlineIf',
                        'attrs' => [
                            'condition' => 'tampered',
                            'conditionAst' => $conditionAst,
                            'conditionId' => 'active-user',
                            'branch' => 'then',
                        ],
                    ]],
                ]],
            ],
        ],
    ]);

    expect($editor->getConditions())
        ->toHaveKeys(['truthy', 'comparison:equals', 'test:defined'])
        ->and($document['content'][0]['attrs']['condition'])
        ->toBe('(user.active) or (user.active is defined)')
        ->and($document['content'][1]['content'][0]['marks'][0]['attrs']['condition'])
        ->toBe('(user.active) or (user.active is defined)');
});

it('compiles and serializes paired inline condition branches', function () {
    $conditionAst = [
        'type' => 'group',
        'operator' => 'and',
        'negated' => false,
        'children' => [[
            'type' => 'truthy',
            'subject' => ['type' => 'variable', 'name' => 'user.active'],
            'negated' => false,
        ]],
    ];
    $condition = [
        'condition' => 'tampered',
        'conditionAst' => $conditionAst,
        'conditionId' => 'active-user',
    ];
    $editor = stylus_twig_editor([
        'version' => 1,
        'document' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Active',
                        'marks' => [[
                            'type' => 'twigInlineIf',
                            'attrs' => [...$condition, 'branch' => 'then'],
                        ]],
                    ],
                    [
                        'type' => 'text',
                        'text' => 'Inactive',
                        'marks' => [[
                            'type' => 'twigInlineIf',
                            'attrs' => [...$condition, 'branch' => 'else'],
                        ]],
                    ],
                ],
            ]],
        ],
        'template' => 'tampered',
    ], configure: fn (TwigEditor $editor) => $editor->variables(
        Variable::make('user.active', 'boolean'),
    ));

    expect($editor->getState()['template'])
        ->toBe('<p>{% if (user.active) %}Active{% else %}Inactive{% endif %}</p>');
});

it('compiles and serializes nested inline conditions', function () {
    $condition = static fn (string $variable, string $id) => [
        'type' => 'twigInlineIf',
        'attrs' => [
            'condition' => 'tampered',
            'conditionAst' => [
                'type' => 'group',
                'operator' => 'and',
                'negated' => false,
                'children' => [[
                    'type' => 'truthy',
                    'subject' => ['type' => 'variable', 'name' => $variable],
                    'negated' => false,
                ]],
            ],
            'conditionId' => $id,
            'branch' => 'then',
        ],
    ];
    $outerCondition = $condition('user.visible', 'visible-user');
    $innerCondition = $condition('user.active', 'active-user');
    $editor = stylus_twig_editor([
        'version' => 1,
        'document' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Hello ',
                        'marks' => [$outerCondition],
                    ],
                    [
                        'type' => 'text',
                        'text' => 'member',
                        'marks' => [$outerCondition, $innerCondition],
                    ],
                ],
            ]],
        ],
        'template' => 'tampered',
    ], configure: fn (TwigEditor $editor) => $editor->variables(
        Variable::make('user.visible', 'boolean'),
        Variable::make('user.active', 'boolean'),
    ));

    expect($editor->getState()['template'])
        ->toBe('<p>{% if (user.visible) %}Hello {% if (user.active) %}member{% endif %}{% endif %}</p>');
});

it('rejects unstructured persisted conditions', function (array $node, string $message) {
    expect(fn () => stylus_twig_editor()->compileStructuredConditions([
        'type' => 'doc',
        'content' => [$node],
    ]))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'block condition' => [
        [
            'type' => 'twigIf',
            'attrs' => ['condition' => 'user.active'],
        ],
        'Twig condition nodes must contain a structured condition AST.',
    ],
    'inline condition' => [
        [
            'type' => 'paragraph',
            'content' => [[
                'type' => 'text',
                'text' => 'Active',
                'marks' => [[
                    'type' => 'twigInlineIf',
                    'attrs' => ['condition' => 'user.active'],
                ]],
            ]],
        ],
        'Twig inline conditions must contain a structured condition AST.',
    ],
    'table row condition' => [
        [
            'type' => 'tableRow',
            'attrs' => [
                'twigCondition' => 'user.active',
                'twigConditionId' => 'active-user',
            ],
        ],
        'Twig table row conditions must contain a structured condition AST.',
    ],
]);

it('compiles filters applied to structured condition operands', function () {
    $editor = stylus_twig_editor()
        ->variables(Variable::text('user.email'))
        ->filters(Filter::make('trim')->types('text'))
        ->conditions(Condition::comparison('equals', '==')->types('text'));
    $conditionAst = [
        'type' => 'group',
        'operator' => 'and',
        'negated' => false,
        'children' => [[
            'type' => 'comparison',
            'left' => [
                'type' => 'variable',
                'name' => 'user.email',
                'filters' => [['name' => 'trim', 'arguments' => []]],
            ],
            'operator' => 'equals',
            'right' => ['type' => 'literal', 'valueType' => 'string', 'value' => 'ada@example.com'],
            'negated' => false,
        ]],
    ];

    expect($editor->serializeCondition($conditionAst))
        ->toBe("(user.email|trim == 'ada@example.com')");
});

it('compiles table row conditions in the repeated row scope', function () {
    $editor = stylus_twig_editor()->variables(
        Variable::collection('orders')->items(
            Variable::make('order', 'order')->properties(
                Variable::make('active', 'boolean'),
            ),
        ),
    );
    $document = $editor->compileStructuredConditions([
        'type' => 'doc',
        'content' => [[
            'type' => 'table',
            'content' => [[
                'type' => 'tableRow',
                'attrs' => [
                    'twigLoopItem' => 'order',
                    'twigLoopKey' => null,
                    'twigLoopIterable' => 'orders',
                    'twigCondition' => 'tampered',
                    'twigConditionId' => 'active-order',
                    'twigConditionAst' => [
                        'type' => 'group',
                        'operator' => 'and',
                        'negated' => false,
                        'children' => [[
                            'type' => 'truthy',
                            'subject' => ['type' => 'variable', 'name' => 'order.active'],
                            'negated' => false,
                        ]],
                    ],
                ],
                'content' => [[
                    'type' => 'tableCell',
                    'content' => [['type' => 'paragraph']],
                ]],
            ]],
        ]],
    ]);

    expect($document['content'][0]['content'][0]['attrs']['twigCondition'])
        ->toBe('(order.active)');
});

it('keeps comparison and test conditions with the same name separate', function () {
    $editor = stylus_twig_editor()->conditions(
        Condition::comparison('empty', '=='),
        Condition::test('empty'),
    );

    expect($editor->getConditions())->toHaveKeys(['truthy', 'comparison:empty', 'test:empty']);
});

it('regenerates structured condition expressions while dehydrating state', function () {
    $conditionAst = [
        'type' => 'group',
        'operator' => 'and',
        'negated' => false,
        'children' => [[
            'type' => 'truthy',
            'subject' => ['type' => 'variable', 'name' => 'user.active'],
            'negated' => false,
        ]],
    ];
    $editor = stylus_twig_editor([
        'version' => 1,
        'document' => [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'twigIf',
                    'attrs' => [
                        'condition' => 'tampered',
                        'conditionAst' => $conditionAst,
                    ],
                    'content' => [[
                        'type' => 'twigThen',
                        'content' => [['type' => 'paragraph']],
                    ]],
                ],
                [
                    'type' => 'paragraph',
                    'content' => [[
                        'type' => 'text',
                        'text' => 'Active',
                        'marks' => [[
                            'type' => 'twigInlineIf',
                            'attrs' => [
                                'condition' => 'tampered',
                                'conditionAst' => $conditionAst,
                                'conditionId' => 'active-user',
                                'branch' => 'then',
                            ],
                        ]],
                    ]],
                ],
            ],
        ],
        'template' => 'tampered',
    ], configure: fn (TwigEditor $editor) => $editor->variables(
        Variable::make('user.active', 'boolean'),
    ));

    expect($editor->getState())
        ->toMatchArray([
            'template' => implode("\n", [
                '{% if (user.active) %}',
                '<p></p>',
                '{% endif %}',
                '<p>{% if (user.active) %}Active{% endif %}</p>',
            ]),
        ]);
});

it('reports snippet requirements against the lexical variable scope', function () {
    $snippet = Snippet::make('order_heading', [
        ['type' => 'paragraph'],
    ])->requires('order.number', 'user.name');
    $editor = stylus_twig_editor()
        ->variables(
            Variable::text('user.name'),
            Variable::collection('orders')->items(
                Variable::make('order', 'order')->properties(
                    Variable::text('number'),
                ),
            ),
        )
        ->snippets($snippet);

    expect($editor->getMissingSnippetVariables($snippet))
        ->toBe(['order.number'])
        ->and($editor->getMissingSnippetVariables($snippet, [[
            'item' => 'order',
            'key' => null,
            'iterable' => 'orders',
        ]]))
        ->toBe([])
        ->and($editor->getSnippet('order_heading'))
        ->toBe($snippet);
});
