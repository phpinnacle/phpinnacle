<?php

namespace PHPinnacle\Eidos\Tests\Fixtures;

use Filament\Forms\Components\Select;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;
use PHPinnacle\Eidos\Forms\FieldMapping;

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
            ? fn (FieldMapping $component) => [
                'properties' => ['destination' => [
                    'type' => $component->getSourceSettings()['first']['type'] ?? 'string',
                ]],
            ] : ['properties' => ['destination' => ['type' => 'boolean']]];

        if ($this->mappingMode !== null) {
            $target = [
                'properties' => [
                    'destination' => [
                        'type' => $this->mappingMode === 'property' ? 'object' : 'array',
                        'x-mapping' => $this->mappingMode,
                        'items' => ['type' => 'string'],
                    ],
                ],
            ];
        }

        return $schema
            ->statePath('data')
            ->components([
                FieldMapping::make('mapping')
                    ->jsonSchema(source: [
                        'properties' => ['first' => ['type' => 'string'], 'second' => ['type' => 'string']],
                    ], target: $target)
                    ->sourceSettings(fn () => [Select::make('type')
                        ->options(['string' => 'String', 'boolean' => 'Boolean'])
                        ->default('string')
                        ->live()])
                    ->outputSchema(function (FieldMapping $component) {
                        $schema = $component->getSourceSchema();
                        $type = $component->getSourceSettings()['first']['type'] ?? 'string';
                        $schema['properties']['first']['type'] = $type;
                        $schema['properties']['second']['type'] = $type;

                        return $schema;
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
