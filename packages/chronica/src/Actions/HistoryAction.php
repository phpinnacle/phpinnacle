<?php

namespace PHPinnacle\Chronica\Actions;

use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use PHPinnacle\Chronica\Timeline;

class HistoryAction extends Action
{
    private ?Timeline $timeline = null;

    public static function getDefaultName(): ?string
    {
        return 'activity_history';
    }

    public function timeline(Timeline $timeline): self
    {
        $this->timeline = $timeline;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-chronica::messages.action.label'))
            ->icon(config('phpinnacle-chronica.icon', Heroicon::OutlinedArrowsUpDown))
            ->color('gray')
            ->slideOver()
            ->modalHeading(__('phpinnacle-chronica::messages.action.label'))
            ->modalWidth(Width::ExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalContent(fn (Model $record) => ($this->timeline ?? Timeline::make($record::class))->render($record))
            ->visible(fn (Model $record) => Gate::allows('history', $record));
    }
}
