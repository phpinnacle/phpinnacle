<?php

namespace PHPinnacle\Razor\Resources\Templates\Actions;

use Filament\Actions\SelectAction;
use PHPinnacle\Razor\Models\Template;

class HistoryAction extends SelectAction
{
    public static function getDefaultName(): ?string
    {
        return 'activeVersion';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('phpinnacle-razor::resources.template.actions.history'));

        $this->options(function (Template $record) {
            return $record
                ->history
                ->prepend($record)
                ->mapWithKeys(fn (Template $t) => [
                    $t->id => __('phpinnacle-razor::resources.template.history.version', [
                        'version' => $t->version,
                    ]),
                ]);
        });
    }
}
