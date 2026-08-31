<?php

use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Chronica\Concerns\HasHistory;
use Tests\TestCase;

uses(TestCase::class);

final class HistoricalFixture extends Model
{
    use HasHistory;

    protected $fillable = [
        'name',
    ];
}

it('provides activity log options for a model', function () {
    $options = new HistoricalFixture()->getActivitylogOptions();

    expect($options->logName)->toBe(HistoricalFixture::class)->and($options->logOnlyDirty)->toBeTrue();
});
