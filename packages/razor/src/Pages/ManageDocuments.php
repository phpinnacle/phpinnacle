<?php

namespace PHPinnacle\Razor\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Common\Concerns\PageBadge;
use PHPinnacle\Razor\Actions\CreateDocument;
use PHPinnacle\Razor\Forms\Editor;
use PHPinnacle\Razor\Models\Document;
use PHPinnacle\Razor\Services\Registry;
use PHPinnacle\Tempo\Forms\DatePicker;

/**
 * @property-read Model $record
 */
abstract class ManageDocuments extends ManageRelatedRecords
{
    use PageBadge;

    protected static string $relationship = 'documents';

    public static function getNavigationIcon(): ?string
    {
        return config('phpinnacle-razor.navigation.document.icon');
    }

    public static function getNavigationLabel(): string
    {
        return __('phpinnacle-razor::resources.document.pages.list');
    }

    public static function getNavigationSort(): ?int
    {
        return config('phpinnacle-razor.navigation.document.sort');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('number')
                    ->label(__('phpinnacle-razor::resources.document.fields.number'))
                    ->required()
                    ->maxLength(255),
                DatePicker::make('issued_at')
                    ->label(__('phpinnacle-razor::resources.document.fields.issued_at'))
                    ->required(),
                DatePicker::make('expires_at')
                    ->label(__('phpinnacle-razor::resources.document.fields.expires_at')),
                DatePicker::make('signed_at')
                    ->label(__('phpinnacle-razor::resources.document.fields.signed_at')),
                Editor::make('content')
                    ->columnSpanFull(),
            ]);
    }

    public function getTitle(): string
    {
        return __('phpinnacle-razor::resources.document.pages.manage');
    }

    public function table(Table $table): Table
    {
        $sections = $this->getSections();
        $actions = match (count($sections)) {
            0 => [],
            1 => [
                CreateDocument::make()
                    ->label(__('phpinnacle-razor::resources.document.actions.new'))
                    ->record($this->getOwnerRecord())
                    ->section(current($sections)),
            ],
            default => ActionGroup::make([])
                ->label(__('phpinnacle-razor::resources.document.actions.new'))
                ->button()
                ->actions(
                    array_map(fn (string $section) => CreateDocument::make('document.create.' . $section)
                        ->record($this->getOwnerRecord())
                        ->section($section), $sections),
                ),
        };

        return $table
            ->heading(__('phpinnacle-razor::resources.document.pages.list'))
            ->emptyStateIcon(self::getNavigationIcon())
            ->emptyStateHeading(__('phpinnacle-razor::resources.document.empty.heading'))
            ->emptyStateDescription(__('phpinnacle-razor::resources.document.empty.description'))
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['template', 'parent']))
            ->columns([
                TextColumn::make('number')
                    ->label(__('phpinnacle-razor::resources.document.fields.number'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('parent.number')
                    ->label(__('phpinnacle-razor::resources.document.fields.parent'))
                    ->copyable()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('template.section')
                    ->label(__('phpinnacle-razor::resources.document.fields.section'))
                    ->formatStateUsing(fn (Registry $registry, string $state) => $registry->get($state)->label)
                    ->badge(),
                TextColumn::make('template.name')
                    ->label(__('phpinnacle-razor::resources.document.fields.template')),
                TextColumn::make('issued_at')
                    ->label(__('phpinnacle-razor::resources.document.fields.issued_at'))
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('signed_at')
                    ->label(__('phpinnacle-razor::resources.document.fields.signed_at'))
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('expires_at')
                    ->label(__('phpinnacle-razor::resources.document.fields.expires_at'))
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('phpinnacle-razor::resources.document.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('phpinnacle-razor::resources.document.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions($actions)
            ->recordActions([
                Action::make('document.show')
                    ->icon('phosphor-eye')
                    ->color('gray')
                    ->url(fn (Document $record) => route('documents.show', ['id' => $record->id]))
                    ->openUrlInNewTab()
                    ->iconButton(),
                EditAction::make()
                    ->label(__('phpinnacle-razor::resources.document.actions.edit'))
                    ->modalHeading(__('phpinnacle-razor::resources.document.modal.edit.heading'))
                    ->modalDescription(__('phpinnacle-razor::resources.document.modal.edit.description'))
                    ->modalWidth(Width::ScreenTwoExtraLarge)
                    ->slideOver()
                    ->iconButton(),
                DeleteAction::make()
                    ->label(__('phpinnacle-razor::resources.document.actions.delete'))
                    ->iconButton(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label(__('phpinnacle-razor::resources.document.actions.delete')),
            ]);
    }

    abstract protected function getSections(): array;
}
