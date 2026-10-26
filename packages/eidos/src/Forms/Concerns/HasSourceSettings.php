<?php

namespace PHPinnacle\Eidos\Forms\Concerns;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use PHPinnacle\Eidos\Forms\JsonSchema;

/**
 * @phpstan-require-extends Field
 * @phpstan-import-type MappingItem from JsonSchema
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

    abstract public function getSourceRowIndex(string $source): ?int;

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
        $indexes = [];
        foreach ($this->getSources() as $source) {
            $index = $this->getSourceRowIndex($source['id']);
            if ($index !== null) {
                $indexes[$source['id']] = $index;
            }
        }

        return $indexes;
    }

    /** @return array<string, array<string, mixed>> */
    public function getSourceSettings(): array
    {
        /** @var array<int, array{options: array<string, mixed>}> $rows */
        $rows = $this->getRawState() ?? [];
        $settings = [];
        foreach ($this->getSourceRowIndexes() as $source => $index) {
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
            $schemas['settings_' . $key] = $this
                ->makeChildSchema('settings_' . $key)
                ->statePath($index . '.options')
                ->components([
                    Group::make($this->getSourceSettingsComponents((string) $source)),
                ])
                ->getClone();
        }

        return $schemas;
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
