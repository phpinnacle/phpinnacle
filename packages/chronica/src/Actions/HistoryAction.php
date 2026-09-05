<?php

namespace PHPinnacle\Chronica\Actions;

use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
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
            ->registerModalActions([
                Action::make('revert_activity')
                    ->label(__('phpinnacle-chronica::messages.revert.label'))
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->iconButton()
                    ->color('gray')
                    ->tooltip(__('phpinnacle-chronica::messages.revert.label'))
                    ->requiresConfirmation()
                    ->modalHeading(__('phpinnacle-chronica::messages.revert.heading'))
                    ->modalDescription(__('phpinnacle-chronica::messages.revert.description'))
                    ->modalSubmitActionLabel(__('phpinnacle-chronica::messages.revert.submit'))
                    ->successNotificationTitle(__('phpinnacle-chronica::messages.revert.success'))
                    ->successRedirectUrl(fn (): string => Livewire::originalUrl())
                    ->visible(fn (Model $record) => ($this->timeline ?? Timeline::make($record::class))->isRevertable())
                    ->action(function (array $arguments, Model $record, Action $action) {
                        ($this->timeline ?? Timeline::make($record::class))->revert($record, $arguments['activity']);
                        $action->success();
                    }),
            ])
            ->modalContent(fn (
                Model $record,
                Action $action,
            ) => ($this->timeline ?? Timeline::make($record::class))->render(
                $record,
                $action->getModalAction('revert_activity'),
            ))
            ->visible(fn (Model $record) => Gate::allows('history', $record));
    }
}
