<?php

namespace PHPinnacle\Eidos\Forms\Concerns;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use PHPinnacle\Eidos\JsonSchema\SchemaPresentation;

/**
 * @phpstan-require-extends Field
 * @phpstan-import-type MappingItem from SchemaPresentation
 */
trait HasSourceSettings
{
    /** @var array<Component>|Closure(): array<Component> */
    protected array|Closure $sourceSettingsSchema = [];

    /** @var array<string, string>|null */
    private ?array $cachedSourceSettingsKeys = null;

    /** @var array<string, int>|null */
    private ?array $cachedSourceRowIndexes = null;

    /** @return list<MappingItem> */
    abstract public function getSources(): array;

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

    public function isSourceUsed(string $source): bool
    {
        $index = $this->getSourceRowIndex($source);

        /** @var list<array{target: mixed, ...}> $rows */
        $rows = $this->getRawState() ?? [];

        return $index !== null && $rows[$index]['target'] !== null;
    }

    /** @param array<Component>|Closure $schema */
    public function sourceSettings(array|Closure $schema): static
    {
        $this->sourceSettingsSchema = $schema;

        return $this;
    }

    public function hasSourceSettings(): bool
    {
        return $this->sourceSettingsSchema !== [];
    }

    /** @return array<string, string> */
    public function getSourceStateKeys(): array
    {
        $keys = [];
        foreach ($this->getSources() as $source) {
            $keys[$source['id']] = 'source_' . bin2hex($source['id']);
        }

        return $keys;
    }

    /** @return array<string, int> */
    public function getSourceRowIndexes(): array
    {
        $rowsBySource = [];
        foreach (is_array($state = $this->getRawState()) ? $state : [] as $index => $row) {
            if (is_array($row) && ($row['kind'] ?? null) === 'source' && is_string($row['source'] ?? null)) {
                $rowsBySource[$row['source']] ??= $index;
            }
        }

        $indexes = [];
        foreach ($this->getSources() as $source) {
            if (array_key_exists($source['id'], $rowsBySource)) {
                $indexes[$source['id']] = $rowsBySource[$source['id']];
            }
        }

        return $indexes;
    }

    /** @return array<string, array<string, mixed>> */
    public function getSourceSettings(): array
    {
        $schemas = $this->getChildSchemas(withHidden: true);
        $state = [];
        Arr::set($state, $this->getStatePath(), $this->getRawState() ?? []);
        /** @var array<string, mixed> $state */
        $keys = $this->getSourceStateKeys();
        $settings = [];
        foreach ($this->getSourceRowIndexes() as $source => $index) {
            if ($this->hasSourceSettings()) {
                $schema = $schemas['settings_' . $keys[$source]] ?? $this->makeSourceSettingsSchema(
                    (string) $source,
                    $index,
                    $keys[$source],
                );
                $schema->dehydrateState($state);
                $schema->mutateDehydratedState($state);
            }

            /** @var array<int, array{options: array<string, mixed>}> $rows */
            $rows = Arr::get($state, $this->getStatePath(), []);
            $settings[$source] = $rows[$index]['options'];
        }

        return $settings;
    }

    /** @return array<string, Schema> */
    protected function makeSourceSettingsSchemas(): array
    {
        $schemas = [];
        $this->cachedSourceSettingsKeys = $this->getSourceStateKeys();
        $this->cachedSourceRowIndexes = $this->getSourceRowIndexes();
        if (!$this->hasSourceSettings()) {
            return [];
        }

        foreach ($this->cachedSourceRowIndexes as $source => $index) {
            $key = $this->cachedSourceSettingsKeys[$source];
            $schemas['settings_' . $key] = $this->makeSourceSettingsSchema((string) $source, $index, $key);
        }

        return $schemas;
    }

    private function makeSourceSettingsSchema(string $source, int $index, string $key): Schema
    {
        return $this
            ->makeChildSchema('settings_' . $key)
            ->statePath($index . '.options')
            ->components([
                Group::make($this->getSourceSettingsComponents($source)),
            ])
            ->getClone();
    }

    /** @return array<Component> */
    protected function getSourceSettingsComponents(string $source): array
    {
        return $this->evaluate($this->sourceSettingsSchema, ['source' => $source, 'inputSchema' => null]);
    }

    protected function areSourceSettingsSchemasFresh(): bool
    {
        return (
            $this->cachedSourceSettingsKeys === $this->getSourceStateKeys()
            && $this->cachedSourceRowIndexes === $this->getSourceRowIndexes()
        );
    }

    protected function hydrateSourceSettings(): void
    {
        /** @var list<array{options: array<string, mixed>, ...}> $rows */
        $rows = $this->getRawState() ?? [];
        $this->clearCachedChildSchemas();
        foreach ($this->getSources() as $source) {
            $index = $this->getSourceRowIndex($source['id']);
            if ($this->hasSourceSettings() && $index !== null && ($rows[$index]['options'] ?? []) === []) {
                $this->getChildSchemas()['settings_' . $this->getSourceStateKeys()[$source['id']]]->fill();
            }
        }
    }

    protected function initializeSourceRows(): void
    {
        $rows = $this->getRawState() ?? [];
        if (!is_array($rows) || !array_is_list($rows)) {
            return;
        }

        $sources = array_column($this->getSources(), 'id');
        foreach ($sources as $source) {
            if ($this->getSourceRowIndex($source) === null) {
                $rows[] = ['kind' => 'source', 'source' => $source, 'target' => null, 'options' => []];
            }
        }

        $this->rawState($rows);
    }
}
