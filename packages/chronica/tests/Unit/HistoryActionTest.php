<?php

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Chronica\Actions\HistoryAction;
use Tests\TestCase;

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
