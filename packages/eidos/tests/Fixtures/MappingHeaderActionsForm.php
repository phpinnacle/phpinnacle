<?php

namespace PHPinnacle\Eidos\Tests\Fixtures;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;
use PHPinnacle\Eidos\Forms\FieldBinding;
use PHPinnacle\Eidos\Forms\FieldMapping;

class MappingHeaderActionsForm extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public array $data = [];

    public array $saved = [];

    public string $owner = '';

    public bool $binding = false;

    public bool $contained = true;

    public function mount(bool $binding = false, bool $contained = true): void
    {
        $this->binding = $binding;
        $this->contained = $contained;
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        $source = fn (Get $get) => $get('schema');
        $field = $this->binding
            ? FieldBinding::make('mapping')->jsonSchema($source)->simple(TextInput::make('destination')->required())
            : FieldMapping::make('mapping')->jsonSchema(source: $source, target: [
                'properties' => ['destination' => ['type' => 'string']],
            ]);
        $field
            ->contained($this->contained)
            ->sourceSettings([
                Repeater::make('transforms')
                    ->schema([
                        TextInput::make('type')
                            ->required()
                            ->dehydrateStateUsing(fn (string $state) => strtoupper($state)),
                    ])
                    ->defaultItems(0),
            ])
            ->headerActions([
                Action::make('addSource')
                    ->schema([TextInput::make('field')->required()])
                    ->action(function (array $data, FieldMapping|FieldBinding $component, Set $set) {
                        $this->owner = $component::class;
                        $source = $component->getSourceSchema();
                        $source['properties'][$data['field']] = ['type' => 'string'];
                        $state = $component->getState();
                        $state[] = [
                            'kind' => 'source',
                            'source' => $data['field'],
                            'target' => null,
                            'options' => ['transforms' => [['type' => 'trim']]],
                        ];
                        $component->state($state);
                        if ($component instanceof FieldMapping) {
                            $component->jsonSchema(source: $source);
                        } else {
                            $component->jsonSchema($source);
                        }

                        $set('schema', $source);
                    }),
            ]);

        return $schema
            ->statePath('data')
            ->components([
                Hidden::make('schema')->default(['properties' => []])->dehydrated(false),
                $field,
            ]);
    }

    public function submit(): void
    {
        $this->saved = $this->form->getState();
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}<x-filament-actions::modals /></div>';
    }
}
