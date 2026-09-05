<?php

namespace PHPinnacle\Razor\Resources\Templates\Pages;

use AllowDynamicProperties;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Razor\Models\Template;
use PHPinnacle\Razor\Resources\Templates\Actions\HistoryAction;
use PHPinnacle\Razor\Resources\Templates\Actions\PreviewAction;
use PHPinnacle\Razor\Resources\Templates\TemplateResource;

/**
 * @property Template $record
 */
#[AllowDynamicProperties]
class EditTemplate extends EditRecord
{
    public static bool $formActionsAreSticky = true;

    protected static string $resource = TemplateResource::class;

    public ?string $activeVersion = null;

    public function getTitle(): string
    {
        return __('phpinnacle-razor::resources.template.pages.edit');
    }

    public function updatedActiveVersion(): void
    {
        $history = Template::get($this->activeVersion);

        $this->data['name'] = $history->name;
        $this->data['content'] = $history->content;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $this->activeVersion = null;

        return parent::handleRecordUpdate($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            HistoryAction::make(),
            PreviewAction::make(),
            DeleteAction::make()
                ->label(__('phpinnacle-razor::resources.template.actions.delete')),
        ];
    }
}
