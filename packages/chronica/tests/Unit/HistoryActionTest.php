<?php

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\Livewire;
use PHPinnacle\Chronica\Actions\HistoryAction;
use Tests\TestCase;

use function Livewire\store;

uses(TestCase::class);

final class HistoryActionFixture extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';
}

it('opens the record history in a native Filament slide-over', function () {
    $record = new HistoryActionFixture;
    $record->id = 'subject-id';
    $record->exists = true;

    $action = HistoryAction::make()->record($record);

    expect($action)
        ->toBeInstanceOf(Action::class)
        ->and($action->isModalSlideOver())
        ->toBeTrue()
        ->and($action->getModalSubmitAction())
        ->toBeNull()
        ->and($action->hasModalContent())
        ->toBeTrue();
});

it('reloads the page after a successful revert', function () {
    $record = new HistoryActionFixture;
    $record->id = 'subject-id';
    $record->exists = true;

    $livewire = new class extends Component {};

    $revertAction = HistoryAction::make()
        ->record($record)
        ->livewire($livewire)
        ->getModalAction('revert_activity');

    $revertAction->dispatchSuccessRedirect();

    expect(store($livewire)->get('redirect'))
        ->toBe(Livewire::originalUrl());
});
