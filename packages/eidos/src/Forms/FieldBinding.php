<?php

namespace PHPinnacle\Eidos\Forms;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\CanBeCompact;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Concerns\CanBeContained;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Js;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PHPinnacle\Eidos\Forms\Concerns\HasColumnSpans;
use PHPinnacle\Eidos\Forms\Concerns\HasHeaderActions;
use PHPinnacle\Eidos\Forms\Concerns\HasSourceSettings;
use PHPinnacle\Eidos\Forms\Concerns\Searchable;

/**
 * @phpstan-import-type MappingItem from JsonSchema
 * @phpstan-import-type Definition from JsonSchema
 * @phpstan-type SourceState array{kind: 'source', source: string, target: mixed, options: array<string, mixed>}
 * @phpstan-type StaticState array{kind: 'static', target: mixed, static: mixed, options: array<string, mixed>}
 * @phpstan-type State list<SourceState|StaticState>
 */
class FieldBinding extends Field
{
    use CanBeCompact;
    use CanBeContained;
    use HasColumnSpans;
    use HasHeaderActions;
    use HasSourceSettings;
    use Searchable;

    private const string CONSTANT_PREFIX = 'constant_';

    protected string $view = 'phpinnacle-eidos::forms.field-binding';

    /** @var Definition|Closure(): Definition */
    protected array|Closure $jsonSchema = [];

    /** @var string|Closure(): string */
    protected string|Closure $sourceLabel = 'Source';

    /** @var string|Closure(): string */
    protected string|Closure $targetLabel = 'Binding';

    /** @var Field|Closure(): Field|null */
    protected Field|Closure|null $simpleField = null;

    protected bool $hasStaticValues = false;

    /** @var array<string, string>|Closure(): array<string, string> */
    protected array|Closure $warnings = [];

    /** @var Definition|null */
    private ?array $cachedSourceDefinition = null;

    /** @var list<MappingItem> */
    private array $cachedSources = [];

    /** @var list<int> */
    private array $boundRowsBeforeDehydration = [];

    /** @param array<string, string>|Closure(): array<string, string> $warnings */
    public function warnings(array|Closure $warnings): static
    {
        $this->warnings = $warnings;

        return $this;
    }

    /** @return array<string, string> */
    public function getWarnings(): array
    {
        return $this->evaluate($this->warnings);
    }

    /** @param Definition|Closure $schema */
    public function jsonSchema(array|Closure $schema): static
    {
        $this->jsonSchema = $schema;

        return $this;
    }

    /**
     * @param string|Closure(): string $source
     * @param string|Closure(): string $dest
     */
    public function labels(string|Closure $source = 'Source', string|Closure $dest = 'Binding'): static
    {
        $this->sourceLabel = $source;
        $this->targetLabel = $dest;

        return $this;
    }

    public function getSourceLabel(): string
    {
        return $this->evaluate($this->sourceLabel);
    }

    public function getTargetLabel(): string
    {
        return $this->evaluate($this->targetLabel);
    }

    /** @return Definition */
    public function getSourceSchema(): array
    {
        return $this->evaluate($this->jsonSchema);
    }

    /** @return list<MappingItem> */
    public function getSources(): array
    {
        $definition = $this->getSourceSchema();
        if ($this->cachedSourceDefinition !== $definition) {
            $this->cachedSources = JsonSchema::fromArray($definition)->toMappingItems();
            $this->cachedSourceDefinition = $definition;
        }

        return $this->cachedSources;
    }

    /** @param Field|Closure(): Field|null $field */
    public function simple(Field|Closure|null $field): static
    {
        $this->simpleField = $field;
        $this->schema(fn (self $component) => [$component->getSimpleField()]);

        return $this;
    }

    public function getSimpleField(): ?Field
    {
        return $this->simpleField !== null ? $this->evaluate($this->simpleField) : null;
    }

    public function staticValues(bool $condition = true): static
    {
        $this->hasStaticValues = $condition;

        return $this;
    }

    public function hasStaticValues(): bool
    {
        return $this->hasStaticValues;
    }

    public function getBindingKey(string $source): string
    {
        return 'source_' . bin2hex($source);
    }

    /** @return array<Schema> */
    public function getBindings(): array
    {
        return array_filter(
            $this->getCachedDefaultChildSchemas(),
            static fn (string $key) => !str_starts_with($key, 'settings_') && !str_starts_with($key, 'row_'),
            ARRAY_FILTER_USE_KEY,
        );
    }

    public function getBindingSchema(string $source): Schema
    {
        return (
            $this->getBindings()[$this->getBindingKey($source)] ?? $this->makeBindingSchema(
                $source,
                $this->getSourceRowIndexes()[$source],
            )
        );
    }

    public function getBindingDefault(string $source): mixed
    {
        /** @var State $state */
        $state = $this->getRawState();
        $index = $this->getSourceRowIndexes()[$source];

        try {
            $rows = $state;
            $rows[$index]['target'] = $this->simpleField === null ? [] : null;
            $this->rawState($rows);
            $this->makeBindingSchema($source, $index)->fill();

            return $this->getBindingValues()[$index]['target'];
        } finally {
            $this->rawState($state);
        }
    }

    public function renderBindingSchema(string $source, mixed $default): string
    {
        $state = $this->getBindingValues();
        $index = $this->getSourceRowIndexes()[$source];

        try {
            $rows = $state;
            $rows[$index]['target'] ??= $default;
            $this->rawState($rows);

            return $this->getBindingSchema($source)->toHtml();
        } finally {
            $this->rawState($state);
        }
    }

    /** @return State */
    public function getBindingValues(): array
    {
        /** @var State */
        return $this->getState() ?? [];
    }

    /** @return array<Schema> */
    public function getConstants(): array
    {
        return array_filter($this->getBindings(), self::isConstantKey(...), ARRAY_FILTER_USE_KEY);
    }

    public function getSourceRowIndex(string $source): ?int
    {
        foreach (is_array($state = $this->getRawState()) ? $state : [] as $index => $row) {
            if (!is_array($row) || ($row['kind'] ?? null) === null) {
                continue;
            }

            if ($row['kind'] === 'source' && $row['source'] === $source) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $hydratedDefaultState
     * @param array<string, true> $appliedStateCastPaths
     */
    public function hydrateState(
        ?array &$hydratedDefaultState,
        bool $shouldCallHydrationHooks = true,
        bool $shouldApplyStateCasts = true,
        array &$appliedStateCastPaths = [],
    ): void {
        $this->hydrateDefaultState($hydratedDefaultState);
        $this->initializeSourceRows();
        /** @var State $rows */
        $rows = $this->getRawState() ?? [];
        $this->clearCachedChildSchemas();
        foreach ($this->getSources() as $source) {
            $index = $this->getSourceRowIndex($source['id']);
            if ($this->hasSourceSettings() && $index !== null && ($rows[$index]['options'] ?? []) === []) {
                $this->getChildSchemas()['settings_' . $this->getSourceStateKeys()[$source['id']]]->fill();
            }
        }

        parent::hydrateState(
            $hydratedDefaultState,
            $shouldCallHydrationHooks,
            $shouldApplyStateCasts,
            $appliedStateCastPaths,
        );
    }

    /** @return array<Schema> */
    public function getDefaultChildSchemas(): array
    {
        $state = $this->getRawState();
        if ($state !== null && (!is_array($state) || !array_is_list($state))) {
            return [];
        }

        $this->initializeSourceRows();
        $schemas = [];
        foreach (is_array($state = $this->getRawState()) ? $state : [] as $index => $row) {
            if (!is_array($row) || ($row['kind'] ?? null) === null) {
                continue;
            }

            $schemas['row_' . $index] = $this
                ->makeChildSchema('row_' . $index)
                ->statePath((string) $index)
                ->components(array_map(
                    fn (string $name) => Hidden::make($name)->default($row[$name])->rules(['present']),
                    array_keys($row),
                ));
        }

        $schemas += $this->makeSourceSettingsSchemas();
        foreach (is_array($state = $this->getRawState()) ? $state : [] as $index => $row) {
            if (!is_array($row) || ($row['kind'] ?? null) === null) {
                continue;
            }

            if (
                $row['kind'] === 'source'
                && $row['target'] !== null
                && in_array($row['source'], array_column($this->getSources(), 'id'), true)
            ) {
                $schemas[$this->getBindingKey($row['source'])] = $this->makeBindingSchema($row['source'], $index);
            } elseif ($row['kind'] === 'static' && $this->hasStaticValues()) {
                $key = self::CONSTANT_PREFIX . $index;
                $schemas[$key] = $this
                    ->getConstantSchema($key)
                    ->statePath((string) $index)
                    ->getClone();
            }
        }

        return $schemas;
    }

    private function makeBindingSchema(string $source, int $index): Schema
    {
        /** @var Schema $schema The default child schema always exists. */
        $schema = $this->getChildSchema();
        $schema = $schema->getClone()->inlineLabel(false);
        if ($this->simpleField !== null) {
            /** @var Field $field */
            $field = $schema->getComponents()[0];
            $field->statePath('target')->key($field->getName());
            if (!$field->hasDefaultState()) {
                $field->default($source);
            }

            $schema->statePath((string) $index);
        } else {
            $schema->statePath($index . '.target');
        }

        return $schema;
    }

    protected function areCachedDefaultChildSchemasFresh(): bool
    {
        return (
            array_keys(array_filter(
                $this->cachedDefaultChildSchemas ?? [],
                fn (string $key) => str_starts_with($key, 'source_') || self::isConstantKey($key),
                ARRAY_FILTER_USE_KEY,
            )) === $this->getBoundKeys()
            && $this->areSourceSettingsSchemasFresh()
        );
    }

    private function getConstantSchema(string $key, ?string $defaultTarget = null): Schema
    {
        $fields = $this->evaluate($this->sourceSettingsSchema, [
            'source' => $key,
            'inputSchema' => fn () => fn () => $this->getConstantInputSchema($key),
        ]);
        /** @var Schema $output The default child schema always exists. */
        $output = $this->getChildSchema();
        $output = $output->getClone()->inlineLabel(false);
        if ($this->simpleField !== null) {
            /** @var Field $target */
            $target = $output->getComponents()[0];
            $target->statePath('target')->key($target->getName());
            if ($defaultTarget !== null && !$target->hasDefaultState()) {
                $target->default($defaultTarget);
            }
        }

        $outputGroup = Group::make()->schema($output)->key('output');
        if ($this->simpleField === null) {
            $outputGroup
                ->statePath('target')
                ->default([])
                ->dehydrateStateUsing(fn (?array $state) => $state ?? []);
        }

        return $this->makeChildSchema('constants')->components([
            Group::make([
                TextInput::make('static')
                    ->label(__('phpinnacle-eidos::schema-forms.value'))
                    ->hiddenLabel()
                    ->inlineLabel(false)
                    ->placeholder(__('phpinnacle-eidos::schema-forms.value'))
                    ->default('')
                    ->required()
                    ->rule(fn () => $this->validateStaticValue(...)),
                Group::make($fields)->statePath('options')->default([]),
            ])->key('input')->dense(),
            $outputGroup,
        ]);
    }

    public function getConstantInputSchema(string $constant): JsonSchema
    {
        /** @var State $rows */
        $rows = $this->getRawState();
        /** @var StaticState $row */
        $row = $rows[$this->constantIndex($constant)];
        $value = $row['static'];

        return JsonSchema::fromArray(['type' => match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'number',
            is_array($value) => 'array',
            is_object($value) => 'object',
            default => 'string',
        }]);
    }

    public function getConstantTarget(string $constant): mixed
    {
        /** @var State $rows */
        $rows = $this->getRawState();

        return $rows[$this->constantIndex($constant)]['target'];
    }

    private function constantIndex(string $constant): int
    {
        return (int) substr($constant, strlen(self::CONSTANT_PREFIX));
    }

    public function getBindAction(): Action
    {
        return Action::make('bind')
            ->label(__('phpinnacle-eidos::schema-forms.binding.bind'))
            ->icon('heroicon-m-link')
            ->iconButton()
            ->size('lg')
            ->tooltip(__('phpinnacle-eidos::schema-forms.binding.bind'))
            ->alpineClickHandler(
                fn (array $arguments) => (
                    'bind('
                    . Js::from($arguments['source'])->toHtml()
                    . ', '
                    . Js::from($arguments['default'])->toHtml()
                    . ')'
                ),
            )
            ->disabled(fn (self $component) => $component->isDisabled());
    }

    public function getUnbindAction(): Action
    {
        return Action::make('unbind')
            ->label(__('phpinnacle-eidos::schema-forms.binding.unbind'))
            ->alpineClickHandler(fn (array $arguments) => 'unbind(' . Js::from($arguments['source'])->toHtml() . ')')
            ->disabled(fn (self $component) => $component->isDisabled());
    }

    public function getAddConstantAction(): Action
    {
        return Action::make('addConstant')
            ->label(__('phpinnacle-eidos::schema-forms.binding.add_constant'))
            ->icon('heroicon-m-plus')
            ->color('gray')
            ->button()
            ->disabled(fn (self $component) => $component->isDisabled() || !$component->hasStaticValues())
            ->action(fn (self $component) => $component->addConstant())
            ->after(fn (self $component) => $component->renderStaticValues());
    }

    public function getRemoveConstantAction(): Action
    {
        return Action::make('removeConstant')
            ->label(__('phpinnacle-eidos::schema-forms.binding.remove_constant'))
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->iconButton()
            ->disabled(fn (self $component) => $component->isDisabled() || !$component->hasStaticValues())
            ->action(fn (array $arguments, self $component) => $component->removeConstant($arguments))
            ->after(fn (self $component) => $component->renderStaticValues());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->schema([]);
        $this->childComponents([
            View::make('phpinnacle-eidos::forms.field-binding-static-values')->key('staticValues'),
        ], 'staticValues');
        $this->default([]);
        $this->beforeStateDehydrated(function () {
            $this->boundRowsBeforeDehydration = [];
            /** @var State $rows */
            $rows = $this->getRawState() ?? [];
            foreach ($rows as $index => $row) {
                if ($row['target'] !== null) {
                    $this->boundRowsBeforeDehydration[] = $index;
                }
            }
        });
        $this->mutateDehydratedStateUsing(function (?array $state) {
            /** @var State $state */
            if ($this->simpleField !== null) {
                foreach ($this->boundRowsBeforeDehydration as $index) {
                    if ($state[$index]['target'] === null) {
                        throw ValidationException::withMessages([
                            $this->getStatePath() ?? $this->getName() => __(
                                'A bound field must have a non-null value.',
                            ),
                        ]);
                    }
                }
            }

            return $state;
        });
        $this->rules(fn (self $component) => ['bail', 'array', $component->validateBindings(...)]);
        $this->registerActions([
            fn (self $component) => $component->getBindAction(),
            fn (self $component) => $component->getUnbindAction(),
            fn (self $component) => $component->getAddConstantAction(),
            fn (self $component) => $component->getRemoveConstantAction(),
        ]);
    }

    private function renderStaticValues(): void
    {
        /** @var Component $component */
        $component = $this->getChildSchemas()['staticValues']->getComponent('staticValues');
        $component->partiallyRender();
    }

    private function addConstant(): void
    {
        if ($this->isDisabled() || !$this->hasStaticValues()) {
            return;
        }

        $targets = array_column($this->getBindingValues(), 'target');
        $number = 1;
        while (in_array('static_' . $number, $targets, true)) {
            $number++;
        }

        /** @var State $rows */
        $rows = $this->getRawState() ?? [];
        $index = count($rows);
        $rows[] = ['kind' => 'static', 'target' => null, 'static' => '', 'options' => []];
        $this->rawState($rows);
        $this->clearCachedChildSchemas();
        $this
            ->getConstantSchema(self::CONSTANT_PREFIX . $index, 'static_' . $number)
            ->statePath((string) $index)
            ->fill();
        $this->callAfterStateUpdated();
    }

    /** @param array<array-key, mixed> $arguments */
    private function removeConstant(array $arguments): void
    {
        if ($this->isDisabled() || !$this->hasStaticValues()) {
            return;
        }

        /** @var array{constant: string} $validated */
        $validated = Validator::make($arguments, [
            'constant' => ['required', 'string', Rule::in(array_keys($this->getConstants()))],
        ])->validate();
        /** @var State $rows */
        $rows = $this->getRawState();
        unset($rows[$this->constantIndex($validated['constant'])]);
        $this->rawState(array_values($rows));
        $this->clearCachedChildSchemas();
        $this->callAfterStateUpdated();
    }

    public function isSourceUsed(string $source): bool
    {
        $index = $this->getSourceRowIndex($source);

        /** @var State $rows */
        $rows = $this->getRawState() ?? [];

        return $index !== null && $rows[$index]['target'] !== null;
    }

    /** @return list<string> */
    private function getBoundKeys(): array
    {
        $keys = [];
        foreach (is_array($state = $this->getRawState()) ? $state : [] as $index => $row) {
            if (!is_array($row) || ($row['kind'] ?? null) === null) {
                continue;
            }

            if (
                $row['kind'] === 'source'
                && $row['target'] !== null
                && in_array($row['source'], array_column($this->getSources(), 'id'), true)
            ) {
                $keys[] = $this->getBindingKey($row['source']);
            } elseif ($row['kind'] === 'static' && $this->hasStaticValues()) {
                $keys[] = self::CONSTANT_PREFIX . $index;
            }
        }

        return $keys;
    }

    private static function isConstantKey(string $key): bool
    {
        return str_starts_with($key, self::CONSTANT_PREFIX);
    }

    /** @param Closure(string): PotentiallyTranslatedString $fail */
    private function validateStaticValue(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_scalar($value)) {
            $fail('The :attribute field must be a scalar value.')->translate();
        }
    }

    /** @param Closure(string): PotentiallyTranslatedString $fail */
    private function validateBindings(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            return;
        }

        $validator = Validator::make(['rows' => $value], [
            'rows' => ['array', 'list'],
            'rows.*' => Rule::forEach(fn (mixed $row) => [Rule::array(
                is_array($row) && ($row['kind'] ?? null) === 'source'
                    ? ['kind', 'source', 'target', 'options']
                    : ['kind', 'target', 'static', 'options'],
            )]),
            'rows.*.kind' => ['required', Rule::in($this->hasStaticValues() ? ['source', 'static'] : ['source'])],
            'rows.*.target' => ['present'],
            'rows.*.options' => ['present', 'array'],
            'rows.*.source' => [
                'required_if:rows.*.kind,source',
                'string',
                Rule::in(array_column($this->getSources(), 'id')),
            ],
            'rows.*.static' => ['present_if:rows.*.kind,static'],
        ]);
        if ($validator->fails()) {
            $fail($validator->errors()->first())->translate();

            return;
        }

        /** @var array{rows: State} $validated */
        $validated = $validator->validated();
        $value = $validated['rows'];
        $sources = [];
        $staticTargets = [];
        foreach ($value as $row) {
            if ($row['kind'] === 'source') {
                $source = $row['source'];
                if (array_key_exists($source, $sources)) {
                    $fail('The :attribute field contains an unknown or duplicate source.')->translate();

                    return;
                }

                $sources[$source] = true;
            }

            if ($row['kind'] === 'static' && $this->simpleField !== null) {
                if ($row['target'] === null || in_array($row['target'], $staticTargets, true)) {
                    $fail('The :attribute field contains duplicate or null static targets.')->translate();

                    return;
                }

                $staticTargets[] = $row['target'];
            }

            if ($row['target'] !== null && $this->simpleField === null && !is_array($row['target'])) {
                $fail('The :attribute field contains an invalid binding value.')->translate();

                return;
            }
        }
    }
}
