<?php

namespace PHPinnacle\Chronica\Actions;

use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use PHPinnacle\Chronica\Timeline;
use RalphJSmit\Filament\Activitylog\Filament\Actions\TimelineAction;
use RalphJSmit\Filament\Activitylog\Filament\Infolists\Components\Timeline as Component;

class HistoryAction extends TimelineAction
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
            ->visible(fn (Model $record) => Gate::allows('history', $record))
            ->modifyTimelineUsing($this->applyModify(...));
    }

    private function applyModify(Component $timeline): Component
    {
        $builder = $this->timeline ?? new Timeline($this->getModel());

        return $builder->apply($timeline);
    }
}
