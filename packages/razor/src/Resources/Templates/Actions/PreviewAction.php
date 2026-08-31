<?php

namespace PHPinnacle\Razor\Resources\Templates\Actions;

use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use PHPinnacle\Razor\Models\Template;
use PHPinnacle\Razor\Services\Registry;
use PHPinnacle\Razor\Services\Renderer;

class PreviewAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'previewTemplate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('phpinnacle-razor::resources.template.actions.preview'));

        $this->visible(
            fn (Registry $registry, Template $record) => (
                $registry->has($record->section) && $registry->get($record->section)->hasPreviewAction()
            ),
        );
        $this->schema(fn (Registry $registry, Template $record) => [
            ...$registry->get($record->section)->getPreviewForm(),
            TextEntry::make('view')
                ->key('view')
                ->visible(false),
        ]);
        $this->action(function (Registry $registry, Renderer $renderer, Schema $schema, Template $record, array $data) {
            $section = $registry->get($record->section);
            $data = $section->getPreviewData($data);

            $content = $renderer->render($record, $data);
            $view = $schema->getComponent('view', withHidden: true);
            $view->visible();
            $view->state(new HtmlString($content));

            $this->halt();
        });
    }
}
