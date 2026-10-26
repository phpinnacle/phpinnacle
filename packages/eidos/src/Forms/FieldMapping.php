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
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Rule;
use PHPinnacle\Eidos\Forms\Concerns\HasColumnSpans;
use PHPinnacle\Eidos\Forms\Concerns\HasHeaderActions;
use PHPinnacle\Eidos\Forms\Concerns\HasSourceSettings;
use PHPinnacle\Eidos\Forms\Concerns\Searchable;

/**
 * @phpstan-import-type MappingItem from JsonSchema
 * @phpstan-import-type Definition from JsonSchema
 * @phpstan-import-type Presentation from JsonSchema
 * @phpstan-type SourceEntry array{kind: 'source', source: string}
 * @phpstan-type ValueEntry array{kind: 'value', value: mixed}
 * @phpstan-type Entry SourceEntry|ValueEntry
 * @phpstan-type Targets array<string, Entry|non-empty-list<SourceEntry>>
 * @phpstan-type SourceState array{kind: 'source', source: string, target: string|null, options: array<string, mixed>}
 * @phpstan-type StaticState array{kind: 'static', target: string|null, static: mixed, options: array<string, mixed>}
 * @phpstan-type State list<SourceState|StaticState>
 */
// @mago-expect lint:too-many-properties
class FieldMapping extends Field
{
    use CanBeCompact;
    use CanBeContained;
    use HasColumnSpans;
    use HasHeaderActions;
    use HasSourceSettings;
    use Searchable;

    protected string $view = 'phpinnacle-eidos::forms.field-mapping';

    /** @var Definition|Closure(): Definition */
    protected array|Closure $sourceSchema = [];

    /** @var Definition|Closure(): Definition */
    protected array|Closure $targetSchema = [];

    /** @var Definition|Closure(): Definition|null */
    protected array|Closure|null $outputSchema = null;

    protected ?SchemaCompatibility $compatibility = null;

    /** @var Closure(JsonSchema, JsonSchema, string, string): bool|null */
    protected ?Closure $canConnectUsing = null;

    /** @var string|Closure(): string */
    protected string|Closure $sourceLabel = 'Source';

    /** @var string|Closure(): string */
    protected string|Closure $targetLabel = 'Destination';

    protected ?Closure $staticForms = null;

    protected bool $hasStaticValues = false;

    /** @var bool|Closure(): bool */
    protected bool|Closure $isInverse = false;

    /** @var array<string, string>|Closure(): array<string, string> */
    protected array|Closure $sourceWarnings = [];

    /** @var array<string, string>|Closure(): array<string, string> */
    protected array|Closure $targetWarnings = [];

    /** @var Definition|null */
    private ?array $cachedSourceDefinition = null;

    /** @var list<MappingItem> */
    private array $cachedSources = [];

    /** @var array<string, Presentation>|null */
    private ?array $sourcePresentationsBeforeUpdate = null;

    /** @var array<string, true> */
    private array $updatedSources = [];

    private bool $shouldRenderStaticSources = false;

    /** @var list<string> */
    private array $cachedStaticTargets = [];

    /**
     * @param array<string, string>|Closure(): array<string, string> $source
     * @param array<string, string>|Closure(): array<string, string> $dest
     */
    public function warnings(array|Closure $source = [], array|Closure $dest = []): static
    {
        $this->sourceWarnings = $source;
        $this->targetWarnings = $dest;

        return $this;
    }

    /** @return array<string, string> */
    public function getSourceWarnings(): array
    {
        return $this->evaluate($this->sourceWarnings);
    }

    /** @return array<string, string> */
    public function getTargetWarnings(): array
    {
        return $this->evaluate($this->targetWarnings);
    }

    /** @return list<string> */
    public function getRequiredTargets(): array
    {
        return JsonSchema::fromArray($this->getTargetSchema())->required();
    }

    /** @return list<string> */
    public function getMultipleTargets(): array
    {
        return array_keys(array_filter(
            JsonSchema::fromArray($this->getTargetSchema())->properties(),
            fn (JsonSchema $property) => $property->items() !== null,
        ));
    }

    /** @param Closure $forms Factory returning a Field named value or null, with target, property and other Filament-injected arguments. */
    public function staticForms(Closure $forms): static
    {
        $this->staticForms = $forms;
        $this->schema([]);

        return $this;
    }

    public function withBuiltinStaticForms(): static
    {
        return $this->staticForms(fn (
            FieldMapping $component,
            string $target,
            JsonSchema $property,
        ) => BuiltinStaticForms::make(
            $property,
            in_array($target, $component->getRequiredTargets(), true),
        ));
    }

    public function hasStaticForms(): bool
    {
        return !$this->hasStaticValues() && $this->staticForms !== null;
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

    /** @return array<string, array{static: mixed}> */
    public function getConstants(): array
    {
        if (!$this->hasStaticValues()) {
            return [];
        }

        $constants = [];
        $names = $this->evaluate($this->sourceSchema)['properties'] ?? [];
        foreach (is_array($state = $this->getRawState()) ? $state : [] as $index => $row) {
            if (!is_array($row) || !isset($row['kind'])) {
                continue;
            }
            if ($row['kind'] === 'static') {
                $number = $index + 1;
                while (array_key_exists('static_' . $number, $names)) {
                    $number++;
                }
                $name = 'static_' . $number;
                $names[$name] = true;
                $constants[$name] = ['static' => $row['static']];
            }
        }

        return $constants;
    }

    public function getSourceRowIndex(string $source): ?int
    {
        $constantNames = array_keys($this->getConstants());
        $constant = 0;
        foreach (is_array($state = $this->getRawState()) ? $state : [] as $index => $row) {
            if (!is_array($row) || !isset($row['kind'])) {
                continue;
            }
            if (
                $row['kind'] === 'source'
                && $row['source'] === $source
                || $row['kind'] === 'static'
                && $source === ($constantNames[$constant++] ?? null)
            ) {
                return $index;
            }
        }

        return null;
    }

    public function getConstantValueSchema(string $source): Schema
    {
        return $this
            ->makeChildSchema('constant_' . $source)
            ->statePath((string) $this->getSourceRowIndex($source))
            ->components([
                TextInput::make('static')
                    ->label(__('phpinnacle-eidos::schema-forms.value'))
                    ->hiddenLabel()
                    ->inlineLabel(false)
                    ->placeholder(__('phpinnacle-eidos::schema-forms.value'))
                    ->default('')
                    ->required(fn () => $this->isSourceUsed($source))
                    ->rule(fn () => $this->validateStaticValue(...)),
            ]);
    }

    /** @return array<Component> */
    protected function getSourceSettingsComponents(string $source): array
    {
        $components = $this->evaluate($this->sourceSettingsSchema, [
            'source' => $source,
            'inputSchema' => array_key_exists($source, $this->getConstants())
                ? fn () => fn () => JsonSchema::fromArray($this->getSourceSchema())->properties()[$source]
                : null,
        ]);

        return [Group::make($components)->afterStateUpdated(function () use ($source) {
            $this->updatedSources[$source] = true;
        })];
    }

    /** @return array<string, string> */
    public function getStateKeys(): array
    {
        $keys = [];
        foreach ($this->getTargets() as $target) {
            $keys[$target['id']] = self::stateKey($target['id']);
        }

        return $keys;
    }

    public function getStaticFormSchema(string $target): ?Schema
    {
        $field = $this->makeStaticField($target);
        if ($field === null) {
            return null;
        }

        return $this
            ->makeChildSchema(self::stateKey($target))
            ->statePath((string) $this->getStaticRowIndex($target))
            ->partiallyRender()
            ->components([
                Group::make([
                    $field->statePath('static')->key('value'),
                ])->key('staticValue', isInheritable: false)->partiallyRenderAfterStateUpdated(),
            ]);
    }

    /** @return array<string, mixed> */
    public function getStaticDefaults(): array
    {
        if (!$this->hasStaticForms()) {
            return [];
        }

        $defaults = [];
        foreach (array_column($this->getTargets(), 'id') as $target) {
            $schema = $this->getStaticFormSchema($target);
            if ($schema !== null) {
                /** @var Field $field */
                $field = $schema->getComponent('value');
                $defaults[$target] = $field->getDefaultState();
            }
        }

        return $defaults;
    }

    /** @return array<Schema> */
    public function getDefaultChildSchemas(): array
    {
        $state = $this->getRawState();
        if ($state !== null && (!is_array($state) || !array_is_list($state))) {
            return ['sourceCards' => $this->makeChildSchema('sourceCards')->inline()->components($this->sourceCards())];
        }
        $this->initializeSourceRows();
        $this->sourcePresentationsBeforeUpdate ??= $this->getSourcePresentations();
        $this->cachedStaticTargets = $this->getStaticTargets();
        $schemas = [];
        foreach (is_array($state = $this->getRawState()) ? $state : [] as $index => $row) {
            if (!is_array($row) || !isset($row['kind'])) {
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
        foreach (array_keys($this->getConstants()) as $source) {
            $schemas['constant_' . $source] = $this->getConstantValueSchema($source);
        }
        foreach ($this->cachedStaticTargets as $target) {
            $schema = $this->getStaticFormSchema($target);
            if ($schema !== null) {
                $schemas[self::stateKey($target)] = $schema;
            }
        }
        $schemas['sourceCards'] = $this->makeChildSchema('sourceCards')->inline()->components($this->sourceCards());

        return $schemas;
    }

    protected function areCachedDefaultChildSchemasFresh(): bool
    {
        return $this->cachedStaticTargets === $this->getStaticTargets() && $this->areSourceSettingsSchemasFresh();
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
        /** @var State $rows */
        $rows = $this->getRawState() ?? [];
        $sources = array_column(array_filter($rows, fn (array $row) => $row['kind'] === 'source'), 'source');
        foreach (array_keys($this->evaluate($this->sourceSchema)['properties'] ?? []) as $source) {
            if (!in_array((string) $source, $sources, true)) {
                $rows[] = ['kind' => 'source', 'source' => (string) $source, 'target' => null, 'options' => []];
            }
        }
        $this->rawState($rows);
        foreach ($this->getStaticDefaults() as $target => $value) {
            if ($this->getStaticRowIndex((string) $target) === null && !$this->isTargetUsed((string) $target)) {
                $rows[] = ['kind' => 'static', 'target' => (string) $target, 'static' => $value, 'options' => []];
            }
        }
        $this->rawState($rows);
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

    public function getStaticRowIndex(string $target): ?int
    {
        foreach (is_array($state = $this->getRawState()) ? $state : [] as $index => $row) {
            if (!is_array($row) || !isset($row['kind'])) {
                continue;
            }
            if ($row['kind'] === 'static' && $row['target'] === $target) {
                return $index;
            }
        }

        return null;
    }

    private function isTargetUsed(string $target): bool
    {
        /** @var State $rows */
        $rows = $this->getRawState() ?? [];

        return in_array($target, array_column($rows, 'target'), true);
    }

    /** @param bool|Closure(): bool $condition */
    public function inverse(bool|Closure $condition = true): static
    {
        $this->isInverse = $condition;

        return $this;
    }

    public function isInverse(): bool
    {
        return $this->evaluate($this->isInverse);
    }

    /**
     * @param Definition|Closure $source
     * @param Definition|Closure $dest
     */
    public function jsonSchema(array|Closure|null $source = null, array|Closure|null $dest = null): static
    {
        if ($source !== null) {
            $this->sourceSchema = $source;
        }
        if ($dest !== null) {
            $this->targetSchema = $dest;
        }

        return $this;
    }

    /** @param Definition|Closure $schema */
    public function outputSchema(array|Closure $schema): static
    {
        $this->outputSchema = $schema;

        return $this;
    }

    /** @return Definition */
    public function getSourceSchema(): array
    {
        $schema = $this->evaluate($this->sourceSchema);
        foreach ($this->getConstants() as $source => $constant) {
            $schema['properties'][$source] = [
                'title' => Str::headline($source),
                'type' => match (true) {
                    is_bool($constant['static']) => 'boolean',
                    is_int($constant['static']) => 'integer',
                    is_float($constant['static']) => 'number',
                    default => 'string',
                },
            ];
        }

        return $schema;
    }

    /** @return Definition */
    public function getTargetSchema(): array
    {
        return $this->evaluate($this->targetSchema);
    }

    /** @return Definition */
    public function getOutputSchema(): array
    {
        return $this->outputSchema === null ? $this->getSourceSchema() : $this->evaluate($this->outputSchema);
    }

    public function compatibility(SchemaCompatibility $compatibility): static
    {
        $this->compatibility = $compatibility;

        return $this;
    }

    /** @param Closure(JsonSchema, JsonSchema, string, string): bool $callback */
    public function canConnectUsing(Closure $callback): static
    {
        $this->canConnectUsing = $callback;

        return $this;
    }

    private function canConnect(
        JsonSchema $source,
        JsonSchema $target,
        string $sourceName,
        string $targetName,
    ): bool {
        return (
            $source->compatible($target, $this->compatibility)
            && ($this->canConnectUsing === null || ($this->canConnectUsing)($source, $target, $sourceName, $targetName))
        );
    }

    /** @return array<string, array<string, 'whole'|'item'>> */
    public function getConnectionModes(): array
    {
        $modes = [];
        $outputs = JsonSchema::fromArray($this->getOutputSchema())->properties();
        $targets = JsonSchema::fromArray($this->getTargetSchema())->properties();
        foreach (array_keys(JsonSchema::fromArray($this->getSourceSchema())->properties()) as $source) {
            $output = $outputs[$source] ?? JsonSchema::fromArray([]);
            foreach ($targets as $target => $destination) {
                $items = $destination->items();
                if (
                    ($items === null
                    || $output->items() !== null)
                    && $this->canConnect($output, $destination, $source, $target)
                ) {
                    $modes[$source][$target] = 'whole';
                } elseif ($items !== null && $this->canConnect($output, $items, $source, $target)) {
                    $modes[$source][$target] = 'item';
                }
            }
        }

        return $modes;
    }

    /**
     * @param string|Closure(): string $source
     * @param string|Closure(): string $dest
     */
    public function labels(string|Closure $source = 'Source', string|Closure $dest = 'Destination'): static
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

    /** @return list<MappingItem> */
    public function getTargets(): array
    {
        return JsonSchema::fromArray($this->getTargetSchema())->toMappingItems();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->schema([]);
        $this->childComponents([
            View::make('phpinnacle-eidos::forms.field-mapping-configuration')->key('configuration'),
            View::make('phpinnacle-eidos::forms.field-mapping-static-sources')->key('staticSources'),
        ], 'mappingUi');
        $this->default([]);
        $this->afterStateUpdated(fn (self $component) => $component->renderMappingUpdates());
        $this->required(fn () => $this->getRequiredTargets() !== []);
        $this->rules(fn () => [
            'bail',
            'array',
            'list',
            $this->validateMapping(...),
        ]);
        $this->registerActions([
            fn (self $component) => $component->getAddConstantAction(),
            fn (self $component) => $component->getRemoveConstantAction(),
        ]);
    }

    public function getAddConstantAction(): Action
    {
        return Action::make('addConstant')
            ->label(__('phpinnacle-eidos::schema-forms.binding.add_constant'))
            ->icon('heroicon-m-plus')
            ->color('gray')
            ->button()
            ->disabled(fn () => $this->isDisabled() || !$this->hasStaticValues())
            ->action(function () {
                if ($this->isDisabled() || !$this->hasStaticValues()) {
                    return;
                }
                $this->sourcePresentationsBeforeUpdate ??= $this->getSourcePresentations();
                $this->shouldRenderStaticSources = true;
                /** @var State $rows */
                $rows = $this->getRawState() ?? [];
                $rows[] = ['kind' => 'static', 'target' => null, 'static' => '', 'options' => []];
                $this->rawState($rows);
                $this->clearCachedChildSchemas();
                /** @var non-empty-array<string, array{static: mixed}> $constants */
                $constants = $this->getConstants();
                $source = array_key_last($constants);
                $this->getChildSchemas()['constant_' . $source]->fill();
                if ($this->hasSourceSettings()) {
                    $this->getChildSchemas()['settings_' . $this->getSourceStateKeys()[$source]]->fill();
                }
                $this->callAfterStateUpdated();
            });
    }

    public function getRemoveConstantAction(): Action
    {
        return Action::make('removeConstant')
            ->label(__('phpinnacle-eidos::schema-forms.binding.remove_constant'))
            ->icon('heroicon-m-trash')
            ->color('gray')
            ->iconButton()
            ->disabled(fn () => $this->isDisabled() || !$this->hasStaticValues())
            ->action(function (array $arguments) {
                if ($this->isDisabled() || !$this->hasStaticValues()) {
                    return;
                }
                /** @var array{constant: string} $validated */
                $validated = Validator::make($arguments, [
                    'constant' => ['required', 'string', Rule::in(array_keys($this->getConstants()))],
                ])->validate();
                $source = $validated['constant'];
                if ($this->isSourceUsed($source)) {
                    return;
                }
                $this->sourcePresentationsBeforeUpdate ??= $this->getSourcePresentations();
                $this->shouldRenderStaticSources = true;
                /** @var State $rows */
                $rows = $this->getRawState();
                unset($rows[$this->getSourceRowIndexes()[$source]]);
                $this->rawState(array_values($rows));
                $this->clearCachedChildSchemas();
                $this->callAfterStateUpdated();
            });
    }

    /** @return array<string, Presentation> */
    public function getSourcePresentations(): array
    {
        return array_map(
            fn (JsonSchema $property) => $property->presentation(),
            JsonSchema::fromArray($this->getOutputSchema())->properties(),
        );
    }

    /** @return list<View> */
    private function sourceCards(): array
    {
        $presentations = $this->getSourcePresentations();
        $keys = $this->getSourceStateKeys();

        $components = [];
        foreach ($this->getSources() as $item) {
            $data = [
                'item' => $item,
                'presentation' => $presentations[$item['id']] ?? JsonSchema::fromArray([])->presentation(),
            ];
            $components[] = View::make('phpinnacle-eidos::forms.field-mapping-source-card')
                ->key('card_' . $keys[$item['id']])->viewData($data);
            $components[] = View::make('phpinnacle-eidos::forms.field-mapping-source-heading')
                ->key('heading_' . $keys[$item['id']])
                ->viewData($data);
        }

        return $components;
    }

    private function renderMappingUpdates(): void
    {
        /** @var State $oldRows */
        $oldRows = $this->getOldRawState() ?? [];
        /** @var State $rows */
        $rows = $this->getRawState() ?? [];
        if (
            $this->hasStaticForms()
            || $this->sourceSchema instanceof Closure
            || $this->targetSchema instanceof Closure
            || array_column($oldRows, 'source') !== array_column($rows, 'source')
        ) {
            $this->partiallyRender();

            return;
        }
        // Live updates can reach the parent before its child schemas have been resolved.
        $refreshHeadings = $this->sourcePresentationsBeforeUpdate === null;
        $presentations = $this->getSourcePresentations();
        $schemas = $this->getChildSchemas();
        $keys = $this->getSourceStateKeys();
        $constants = $this->getConstants();
        foreach ($this->getSources() as $item) {
            $source = $item['id'];
            $presentation = $presentations[$source] ?? JsonSchema::fromArray([])->presentation();
            if ($this->shouldRenderStaticSources && array_key_exists($source, $constants)) {
                continue;
            }
            if (
                !array_key_exists($source, $this->updatedSources)
                && $presentation === ($this->sourcePresentationsBeforeUpdate[$source] ?? null)
                && !$refreshHeadings
            ) {
                continue;
            }
            /** @var View $heading */
            $heading = $schemas['sourceCards']->getComponent('heading_' . $keys[$source]);
            $heading->viewData(['item' => $item, 'presentation' => $presentation]);
            /** @var View $card */
            $card = $schemas['sourceCards']->getComponent(($refreshHeadings ? 'heading_' : 'card_') . $keys[$source]);
            $card->viewData(['item' => $item, 'presentation' => $presentation])->partiallyRender();
        }
        if ($this->shouldRenderStaticSources) {
            /** @var View $staticSources */
            $staticSources = $schemas['mappingUi']->getComponent('staticSources');
            $staticSources->partiallyRender();
        }
        /** @var View $configuration */
        $configuration = $schemas['mappingUi']->getComponent('configuration');
        $configuration->partiallyRender();
        $this->sourcePresentationsBeforeUpdate = $presentations;
        $this->updatedSources = [];
        $this->shouldRenderStaticSources = false;
    }

    /** @param Closure(string): PotentiallyTranslatedString $fail */
    private function validateStaticValue(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_scalar($value)) {
            $fail('The :attribute field must be a scalar value.')->translate();
        }
    }

    /** @param Closure(string): PotentiallyTranslatedString $fail */
    private function validateMapping(string $attribute, mixed $value, Closure $fail): void
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
            'rows.*.kind' => ['required', Rule::in(['source', 'static'])],
            'rows.*.target' => ['present', 'nullable', 'string', Rule::in(array_column($this->getTargets(), 'id'))],
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
        $modes = $this->getConnectionModes();
        $usedSources = [];
        $usedTargets = [];
        foreach ($value as $index => $row) {
            if ($row['kind'] === 'source') {
                $source = $row['source'];
                if (
                    isset($usedSources[$source])
                ) {
                    $fail('The :attribute field contains an invalid source.')->translate();

                    return;
                }
                $usedSources[$source] = true;
                if ($row['target'] !== null && !isset($modes[$source][$row['target']])) {
                    $fail('The :attribute field contains incompatible source and destination schemas.')->translate();

                    return;
                }
                $mode = $row['target'] === null ? null : $modes[$source][$row['target']];
            } else {
                if (
                    !$this->hasStaticValues()
                    && $row['target'] === null
                ) {
                    $fail('The :attribute field contains an invalid static value.')->translate();

                    return;
                }
                if (
                    $row['target'] !== null
                    && ($this->hasStaticValues()
                    || $this->makeStaticField($row['target']) === null)
                    && (!is_scalar($row['static'])
                    || $row['static'] === '')
                ) {
                    $fail('The :attribute field contains an invalid static value.')->translate();

                    return;
                }
                $mode = 'whole';
                if ($this->hasStaticValues() && $row['target'] !== null) {
                    $mode = $modes[array_search($index, $this->getSourceRowIndexes(), true)][$row['target']] ?? null;
                }
            }
            if ($row['target'] === null) {
                continue;
            }
            if ($mode === null) {
                $fail('The :attribute field contains incompatible source and destination schemas.')->translate();

                return;
            }
            if (isset($usedTargets[$row['target']]) && ($mode !== 'item' || $usedTargets[$row['target']] !== 'item')) {
                $fail('The :attribute field reuses a destination that only permits one connection.')->translate();

                return;
            }
            $usedTargets[$row['target']] = $mode;
        }
        $targets = array_column($this->getTargets(), null, 'id');
        foreach ($this->getRequiredTargets() as $target) {
            if (!array_key_exists($target, $usedTargets)) {
                $fail(
                    'Connect the required destination: ' . strip_tags($targets[$target]['label'] ?? $target) . '.',
                )->translate();
            }
        }
    }

    public function isSourceUsed(string $source): bool
    {
        $index = $this->getSourceRowIndex($source);

        /** @var State $rows */
        $rows = $this->getRawState() ?? [];

        return $index !== null && $rows[$index]['target'] !== null;
    }

    /** @return list<string> */
    private function getStaticTargets(): array
    {
        return (
            !$this->hasStaticForms()
                ? []
                : array_values(array_filter(
                    array_column($this->getTargets(), 'id'),
                    fn (string $target) => $this->getStaticRowIndex($target) !== null,
                ))
        );
    }

    private static function stateKey(string $target): string
    {
        return 'target_' . bin2hex($target);
    }

    private function makeStaticField(string $target): ?Field
    {
        if (!$this->hasStaticForms()) {
            return null;
        }

        $property = JsonSchema::fromArray($this->getTargetSchema())->properties()[$target];

        /** @var Field|null */
        return $this->evaluate($this->staticForms, ['target' => $target, 'property' => $property]);
    }
}
