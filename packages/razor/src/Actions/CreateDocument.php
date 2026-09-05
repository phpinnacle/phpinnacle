<?php

namespace PHPinnacle\Razor\Actions;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use PHPinnacle\Razor\Models\Document;
use PHPinnacle\Razor\Models\Template;
use PHPinnacle\Razor\Services\Registry;
use PHPinnacle\Razor\Services\Renderer;
use PHPinnacle\Tempo\Forms\DatePicker;

class CreateDocument extends CreateAction
{
    private ?string $section = null;

    private ?array $templates = null;

    private ?string $default = null;

    public function section(string $value): self
    {
        $this->section = $value;

        $this->getTemplates();

        return $this;
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(fn (Registry $registry) => __('phpinnacle-razor::resources.document.actions.create', [
                'label' => $registry->get($this->section)->getLabel(),
            ]))
            ->modalHeading(__('phpinnacle-razor::resources.document.modal.create.heading'))
            ->modalDescription(__('phpinnacle-razor::resources.document.modal.create.description'))
            ->modalIcon('phosphor-file-plus')
            ->fillForm(fn (Model $record) => [
                'number' => $this->default !== null ? Template::get($this->default)->makeNumber($record) : null,
                'created_at' => Date::now(),
                'issued_at' => Date::now(),
                'template_id' => $this->default,
            ])
            ->schema([
                Group::make()
                    ->columns(3)
                    ->schema(fn (Model $record) => [
                        TextInput::make('number')
                            ->label(__('phpinnacle-razor::resources.document.fields.number'))
                            ->required()
                            ->maxLength(255),
                        Select::make('template_id')
                            ->label(__('phpinnacle-razor::resources.document.fields.template'))
                            ->options($this->getTemplates(...))
                            ->afterStateUpdated(function (Set $set, ?string $state, ?string $old) use ($record) {
                                if (blank($state)) {
                                    return;
                                }

                                $previous = $old !== null ? Template::get($old) : null;
                                $template = Template::get($state);

                                $set('number', $template->makeNumber($record));
                            })
                            ->reactive()
                            ->required(),
                        Select::make('parent_id')
                            ->label(__('phpinnacle-razor::resources.document.fields.parent'))
                            ->options(fn () => Document::select($record)),
                    ]),
                Group::make()
                    ->columns()
                    ->schema([
                        DatePicker::make('created_at')
                            ->label(__('phpinnacle-razor::resources.document.fields.created_at'))
                            ->required(),
                        DatePicker::make('signed_at')
                            ->label(__('phpinnacle-razor::resources.document.fields.signed_at')),
                        DatePicker::make('issued_at')
                            ->label(__('phpinnacle-razor::resources.document.fields.issued_at'))
                            ->required(),
                        DatePicker::make('expires_at')
                            ->label(__('phpinnacle-razor::resources.document.fields.expires_at')),
                    ]),
                Group::make()
                    ->schema(fn (ManageRelatedRecords $livewire, Registry $registry) => [
                        ...$registry->get($this->section)->getRenderForm($livewire->getRecord()),
                    ]),
            ])
            ->action(function (Renderer $renderer, Registry $registry, ManageRelatedRecords $livewire, array $data) {
                $record = $livewire->getRecord();
                $section = $registry->get($this->section);

                $document = $section->getDocument($record, $data);
                $document->content = $renderer->render($document->template, $document->context());
                $document->save();

                $this->success();
            });
    }

    private function getTemplates(): array
    {
        if ($this->templates !== null) {
            return $this->templates;
        }

        $templates = Template::active()->where('section', $this->section)->get();

        $this->default = $templates->first(fn (Template $record) => $record->is_default)?->getKey() ?? $templates
            ->first()
            ?->getKey();

        return $this->templates = $templates->pluck('name', 'id')->all();
    }
}
