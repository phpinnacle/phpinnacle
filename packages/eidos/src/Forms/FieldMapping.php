<?php

namespace PHPinnacle\Eidos\Forms;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\CanBeCompact;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema as FilamentSchema;
use Filament\Support\Concerns\CanBeContained;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Js;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Rule;
use PHPinnacle\Eidos\Enums\MappingMode;
use PHPinnacle\Eidos\Forms\Concerns\HasColumnSpans;
use PHPinnacle\Eidos\Forms\Concerns\HasHeaderActions;
use PHPinnacle\Eidos\Forms\Concerns\HasSourceSettings;
use PHPinnacle\Eidos\Forms\Concerns\Searchable;
use PHPinnacle\Eidos\JsonSchema\SchemaPresentation;
use PHPinnacle\Jason\Schema;

/**
 * @phpstan-import-type MappingItem from SchemaPresentation
 * @phpstan-import-type Presentation from SchemaPresentation
 * @phpstan-type SourceEntry array{kind: 'source', source: string}
 * @phpstan-type ValueEntry array{kind: 'value', value: mixed}
 * @phpstan-type Entry SourceEntry|ValueEntry
 * @phpstan-type Targets array<string, Entry|non-empty-list<SourceEntry>>
 * @phpstan-type SourceState array{kind: 'source', source: string, target: string|null, options: array<string, mixed>, position?: int}
 * @phpstan-type StaticState array{kind: 'static', target: string|null, static: mixed, options: array<string, mixed>, position?: int, draftTarget?: string}
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

    /** @var Schema|Closure(): Schema */
    protected Schema|Closure $sourceSchema;

    /** @var Schema|Closure(): Schema */
    protected Schema|Closure $targetSchema;

    /** @var Schema|Closure(): Schema|null */
    protected Schema|Closure|null $outputSchema = null;

    protected ?SchemaCompatibility $compatibility = null;

    /** @var Closure(Schema, Schema, string, string): bool|null */
    protected ?Closure $canConnectUsing = null;

    /** @var string|Closure(): string */
    protected string|Closure $sourceLabel = 'Source';

    /** @var string|Closure(): string */
    protected string|Closure $targetLabel = 'Destination';

    protected ?Closure $staticForms = null;

    /** @var bool|Closure(): bool */
    protected bool|Closure $isInverse = false;

    /** @var array<string, string>|Closure(): array<string, string> */
    protected array|Closure $sourceWarnings = [];

    /** @var array<string, string>|Closure(): array<string, string> */
    protected array|Closure $targetWarnings = [];

    private ?Schema $cachedSourceSchema = null;

    /** @var list<MappingItem> */
    private array $cachedSources = [];

    /** @var array<string, Presentation>|null */
    private ?array $sourcePresentationsBeforeUpdate = null;

    /** @var array<string, true> */
    private array $updatedSources = [];

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
        return $this->getTargetSchema()->resolveReferences()->effectiveRequired();
    }

    /** @return list<string> */
    public function getMultipleTargets(): array
    {
        return array_keys(array_filter(
            $this->getTargetModes(),
            fn (MappingMode $mode) => $mode !== MappingMode::Whole,
        ));
    }

    /** @return array<string, MappingMode> */
    public function getTargetModes(): array
    {
        return array_map(
            self::mappingMode(...),
            $this->getTargetSchema()->resolveReferences()->effectiveProperties(),
        );
    }

    private static function mappingMode(Schema $schema): MappingMode
    {
        return match ([$schema->singleType(), $schema->annotation('x-mapping')]) {
            ['array', MappingMode::Item->value] => MappingMode::Item,
            ['object', MappingMode::Property->value] => MappingMode::Property,
            default => MappingMode::Whole,
        };
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
            Schema $property,
        ) => BuiltinStaticForms::make(
            $property,
            in_array($target, $component->getRequiredTargets(), true),
        ));
    }

    public function hasStaticForms(): bool
    {
        return $this->staticForms !== null;
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

    /** @return array<Component> */
    protected function getSourceSettingsComponents(string $source): array
    {
        $components = $this->evaluate($this->sourceSettingsSchema, [
            'source' => $source,
            'inputSchema' => null,
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

    public function getStaticFormSchema(string $target): ?FilamentSchema
    {
        $field = $this->makeStaticField($target);
        if ($field === null) {
            return null;
        }

        return $this
            ->makeChildSchema(self::stateKey($target))
            ->statePath((string) $this->getStaticRowIndex($target, includeDrafts: true))
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

    /** @return array<FilamentSchema> */
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
            if (!is_array($row) || ($row['kind'] ?? null) === null) {
                continue;
            }

            $names = array_keys($row);
            if (
                $row['kind'] === 'static'
                && in_array($row['target'], $this->cachedStaticTargets, true)
                && $this->makeStaticField($row['target']) !== null
            ) {
                $names = array_values(array_diff($names, ['static']));
            }

            $schemas['row_' . $index] = $this
                ->makeChildSchema('row_' . $index)
                ->statePath((string) $index)
                ->components(array_map(
                    fn (string $name) => Hidden::make($name)->default($row[$name])->rules(['present']),
                    $names,
                ));
        }

        $schemas += $this->makeSourceSettingsSchemas();
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
        foreach (array_keys($this->getSourceSchema()->resolveReferences()->effectiveProperties()) as $source) {
            if (!in_array((string) $source, $sources, true)) {
                $rows[] = ['kind' => 'source', 'source' => (string) $source, 'target' => null, 'options' => []];
            }
        }

        $this->rawState($rows);
        foreach ($this->getStaticDefaults() as $target => $value) {
            if ($this->getStaticRowIndex((string) $target, includeDrafts: true) === null) {
                $row = ['kind' => 'static', 'target' => (string) $target, 'static' => $value, 'options' => []];
                if ($this->isTargetUsed((string) $target)) {
                    $row['target'] = null;
                    $row['draftTarget'] = (string) $target;
                }

                $rows[] = $row;
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

    public function getStaticRowIndex(string $target, bool $includeDrafts = false): ?int
    {
        $draftIndex = null;
        foreach (is_array($state = $this->getRawState()) ? $state : [] as $index => $row) {
            if (!is_int($index) || !is_array($row) || ($row['kind'] ?? null) === null) {
                continue;
            }

            if ($row['kind'] === 'static' && $row['target'] === $target) {
                return $index;
            }

            if ($includeDrafts && $row['kind'] === 'static' && ($row['draftTarget'] ?? null) === $target) {
                $draftIndex = $index;
            }
        }

        return $draftIndex;
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
     * @param Schema|Closure|null $source
     * @param Schema|Closure|null $target
     */
    public function jsonSchema(Schema|Closure|null $source = null, Schema|Closure|null $target = null): static
    {
        if ($source !== null) {
            $this->sourceSchema = $source;
        }

        if ($target !== null) {
            $this->targetSchema = $target;
        }

        return $this;
    }

    /** @param Schema|Closure $schema Schema or Filament-evaluated callback returning Schema. */
    public function outputSchema(Schema|Closure $schema): static
    {
        $this->outputSchema = $schema;

        return $this;
    }

    public function getSourceSchema(): Schema
    {
        return $this->evaluate($this->sourceSchema);
    }

    public function getTargetSchema(): Schema
    {
        return $this->evaluate($this->targetSchema);
    }

    public function getOutputSchema(): Schema
    {
        return $this->outputSchema === null ? $this->getSourceSchema() : $this->evaluate($this->outputSchema);
    }

    public function compatibility(SchemaCompatibility $compatibility): static
    {
        $this->compatibility = $compatibility;

        return $this;
    }

    /** @param Closure(Schema, Schema, string, string): bool $callback */
    public function canConnectUsing(Closure $callback): static
    {
        $this->canConnectUsing = $callback;

        return $this;
    }

    private function canConnect(
        Schema $source,
        Schema $target,
        string $sourceName,
        string $targetName,
    ): bool {
        return (
            ($this->compatibility ?? SchemaCompatibility::make())->accepts($source, $target)
            && ($this->canConnectUsing === null || ($this->canConnectUsing)($source, $target, $sourceName, $targetName))
        );
    }

    /** @return array<string, array<string, MappingMode>> */
    public function getConnectionModes(): array
    {
        $modes = [];
        $outputs = $this->getOutputSchema()->resolveReferences()->effectiveProperties();
        $targets = $this->getTargetSchema()->resolveReferences()->effectiveProperties();
        foreach (array_keys($this->getSourceSchema()->resolveReferences()->effectiveProperties()) as $source) {
            $output = $outputs[$source] ?? Schema::fromArray([]);
            foreach ($targets as $target => $destination) {
                $mode = self::mappingMode($destination);
                $targetSchema = match ($mode) {
                    MappingMode::Item => $destination->arrayItems(),
                    MappingMode::Property => $destination->property((string) $source),
                    MappingMode::Whole => $destination,
                };
                if (
                    $targetSchema !== null
                    && $this->canConnect($output, $targetSchema, (string) $source, (string) $target)
                ) {
                    $modes[$source][$target] = $mode;
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
        $schema = $this->getSourceSchema();
        if ($this->cachedSourceSchema?->toArray() !== $schema->toArray()) {
            $this->cachedSources = SchemaPresentation::toMappingItems($schema);
            $this->cachedSourceSchema = $schema;
        }

        return $this->cachedSources;
    }

    /** @return list<MappingItem> */
    public function getTargets(): array
    {
        return SchemaPresentation::toMappingItems($this->getTargetSchema());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceSchema = Schema::fromArray([]);
        $this->targetSchema = Schema::fromArray([]);

        $this->schema([]);
        $this->childComponents([
            View::make('phpinnacle-eidos::forms.field-mapping-configuration')->key('configuration'),
        ], 'mappingUi');
        $this->default([]);
        $this->mutateDehydratedStateUsing(function (array $state) {
            /** @var list<SourceState|array{kind: 'static', target: string|null, static?: mixed, options: array<string, mixed>, position?: int, draftTarget?: string}> $state */
            foreach ($state as $index => &$row) {
                $row['position'] ??= $index;
            }

            unset($row);
            usort($state, fn (array $a, array $b) => $a['position'] <=> $b['position']);

            return array_values(array_map(
                function (array $row) {
                    unset($row['position'], $row['draftTarget']);

                    return (
                        $row['kind'] === 'source'
                            ? $row
                            : [
                                'kind' => 'static',
                                'target' => $row['target'],
                                'static' => $row['static'],
                                'options' => $row['options'],
                            ]
                    );
                },
                array_filter(
                    $state,
                    fn (array $row) => (
                        $row['kind'] !== 'static'
                        || array_key_exists('static', $row)
                        && ($row['target'] !== null || !array_key_exists('draftTarget', $row))
                    ),
                ),
            ));
        });
        $this->afterStateUpdated(fn (self $component) => $component->renderMappingUpdates());
        $this->required(fn () => $this->getRequiredTargets() !== []);
        $this->rules(fn () => [
            'bail',
            'array',
            'list',
            $this->validateMapping(...),
        ]);
    }

    /** @return array<string, Presentation> */
    public function getSourcePresentations(): array
    {
        return array_map(
            SchemaPresentation::presentation(...),
            $this->getOutputSchema()->resolveReferences()->effectiveProperties(),
        );
    }

    /** @return list<View> */
    private function sourceCards(): array
    {
        $presentations = $this->getSourcePresentations();
        $keys = $this->getSourceStateKeys();

        $components = [];
        foreach ($this->getSources() as $item) {
            $searchText =
                strip_tags($item['label'] . ' ' . ($item['description'] ?? ''))
                . ' '
                . $item['id']
                . ' '
                . $item['name']
                . ' '
                . implode(' ', array_column($item['badges'], 'label'));
            $data = [
                'item' => $item,
                'presentation' =>
                    $presentations[$item['id']] ?? SchemaPresentation::presentation(Schema::fromArray([])),
            ];
            $components[] = View::make('phpinnacle-eidos::forms.field-mapping-source-card')
                ->key('card_' . $keys[$item['id']])
                ->visibleJs('isVisible(\'source\', ' . Js::from($item['id']) . ', ' . Js::from($searchText) . ')')
                ->viewData($data);
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
        foreach ($this->getSources() as $item) {
            $source = $item['id'];
            $presentation = $presentations[$source] ?? SchemaPresentation::presentation(Schema::fromArray([]));
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

        /** @var View $configuration */
        $configuration = $schemas['mappingUi']->getComponent('configuration');
        $configuration->partiallyRender();
        $this->sourcePresentationsBeforeUpdate = $presentations;
        $this->updatedSources = [];
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
                    ? ['kind', 'source', 'target', 'options', 'position']
                    : ['kind', 'target', 'static', 'options', 'position', 'draftTarget'],
            )]),
            'rows.*.kind' => ['required', Rule::in(['source', 'static'])],
            'rows.*.target' => ['present', 'nullable', 'string', Rule::in(array_column($this->getTargets(), 'id'))],
            'rows.*.options' => ['present', 'array'],
            'rows.*.position' => ['sometimes', 'integer', 'min:0'],
            'rows.*.draftTarget' => [
                'sometimes',
                'string',
                Rule::in(array_column($this->getTargets(), 'id')),
            ],
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
        foreach ($value as $row) {
            if ($row['kind'] === 'source') {
                $source = $row['source'];
                if (array_key_exists($source, $usedSources)) {
                    $fail('The :attribute field contains an invalid source.')->translate();

                    return;
                }

                $usedSources[$source] = true;
                if ($row['target'] !== null && ($modes[$source][$row['target']] ?? null) === null) {
                    $fail('The :attribute field contains incompatible source and destination schemas.')->translate();

                    return;
                }

                $mode = $row['target'] === null ? null : $modes[$source][$row['target']];
            } else {
                if (
                    $row['target'] === null
                    && !array_key_exists('draftTarget', $row)
                ) {
                    $fail('The :attribute field contains an invalid static value.')->translate();

                    return;
                }

                if (
                    $row['target'] !== null
                    && $this->makeStaticField($row['target']) === null
                    && (!is_scalar($row['static'])
                    || $row['static'] === '')
                ) {
                    $fail('The :attribute field contains an invalid static value.')->translate();

                    return;
                }

                $mode = MappingMode::Whole;
            }

            if ($row['target'] === null) {
                continue;
            }

            if (
                array_key_exists($row['target'], $usedTargets)
                && ($mode === MappingMode::Whole
                || $usedTargets[$row['target']] !== $mode)
            ) {
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

        $property = $this->getTargetSchema()->resolveReferences()->effectiveProperties()[$target];

        /** @var Field|null */
        return $this->evaluate($this->staticForms, ['target' => $target, 'property' => $property]);
    }
}
