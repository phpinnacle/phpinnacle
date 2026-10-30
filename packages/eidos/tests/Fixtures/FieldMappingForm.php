<?php

namespace PHPinnacle\Eidos\Tests\Fixtures;

use Filament\Forms\Components\Select;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;
use PHPinnacle\Eidos\Forms\FieldMapping;
use PHPinnacle\Eidos\Forms\JsonSchema;

class FieldMappingForm extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** @var array<string, mixed> */
    public array $data = [];

    public bool $dynamicTarget = false;

    public ?string $mappingMode = null;

    public function mount(bool $dynamicTarget = false, ?string $mappingMode = null): void
    {
        $this->dynamicTarget = $dynamicTarget;
        $this->mappingMode = $mappingMode;
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        $target = $this->dynamicTarget
            ? fn (FieldMapping $component) => JsonSchema::fromArray([
                'properties' => ['destination' => [
                    'type' => $component->getSourceSettings()['first']['type'] ?? 'string',
                ]],
            ]) : JsonSchema::fromArray(['properties' => ['destination' => ['type' => 'boolean']]]);

        if ($this->mappingMode !== null) {
            $target = JsonSchema::fromArray([
                'properties' => [
                    'destination' => [
                        'type' => $this->mappingMode === 'property' ? 'object' : 'array',
                        'x-mapping' => $this->mappingMode,
                        'items' => ['type' => 'string'],
                    ],
                ],
            ]);
        }

        return $schema
            ->statePath('data')
            ->components([
                FieldMapping::make('mapping')
                    ->jsonSchema(source: JsonSchema::fromArray([
                        'properties' => ['first' => ['type' => 'string'], 'second' => ['type' => 'string']],
                    ]), target: $target)
                    ->sourceSettings(fn () => [Select::make('type')
                        ->options(['string' => 'String', 'boolean' => 'Boolean'])
                        ->default('string')
                        ->live()])
                    ->outputSchema(function (FieldMapping $component) {
                        $schema = $component->getSourceSchema()->toArray();
                        $type = $component->getSourceSettings()['first']['type'] ?? 'string';
                        $schema['properties']['first']['type'] = $type;
                        $schema['properties']['second']['type'] = $type;

                        return JsonSchema::fromArray($schema);
                    }),
            ]);
    }

    public function submit(): void
    {
        $this->form->getState();
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}
