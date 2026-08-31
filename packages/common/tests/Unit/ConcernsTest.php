<?php

use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPinnacle\Common\Concerns\CanBeRefreshed;
use PHPinnacle\Common\Concerns\HasCreator;
use PHPinnacle\Common\Concerns\PageBadge;
use Tests\TestCase;

uses(TestCase::class);

final class RefreshableFixture
{
    use CanBeRefreshed;
}

final class CreatorFixture extends Model
{
    use HasCreator;
}

abstract class PageBadgeFixture extends Page
{
    use PageBadge;

    protected static string $relationship = 'children';
}

it('provides reusable page and model concerns', function () {
    $refreshable = new RefreshableFixture;
    $refreshable->refresh();

    $creator = new CreatorFixture()->creator();

    expect($refreshable)
        ->toBeInstanceOf(RefreshableFixture::class)
        ->and($creator)
        ->toBeInstanceOf(BelongsTo::class)
        ->and($creator->getForeignKeyName())
        ->toBe('created_by');
});
