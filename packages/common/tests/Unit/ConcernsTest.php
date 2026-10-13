<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\Livewire;
use PHPinnacle\Common\Concerns\CanBeRefreshed;
use PHPinnacle\Common\Concerns\HasCreator;
use Tests\TestCase;

uses(TestCase::class);

final class CreatorFixture extends Model
{
    use HasCreator;

    public $timestamps = false;
}

final class CommonCreatorUser extends User
{
    public $timestamps = false;
}

final class CommonRefreshableFixture extends Component
{
    use CanBeRefreshed;

    public function render(): string
    {
        return '<div>Records: ' . CreatorFixture::query()->count() . '</div>';
    }
}

beforeEach(function () {
    config()->set('auth.providers.users.model', CommonCreatorUser::class);
    Schema::create('common_creator_users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });
    Schema::create('creator_fixtures', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('created_by')->nullable();
    });
});

it('records the authenticated creator and retrieves their identity with the record', function () {
    $user = new CommonCreatorUser()->forceFill(['name' => 'Anna']);
    $user->save();
    Auth::login($user);
    $record = new CreatorFixture;
    $record->save();

    expect($record->fresh()->creator->is($user))->toBeTrue();
});

it('preserves an explicitly assigned creator', function () {
    $author = new CommonCreatorUser()->forceFill(['name' => 'Author']);
    $author->save();
    $operator = new CommonCreatorUser()->forceFill(['name' => 'Operator']);
    $operator->save();
    Auth::login($operator);
    $record = new CreatorFixture;
    $record->created_by = $author->id;
    $record->save();

    expect($record->fresh()->creator->is($author))->toBeTrue();
});

it('shows current records after receiving a page refresh event', function () {
    $page = Livewire::test(CommonRefreshableFixture::class)->assertSee('Records: 0');
    $record = new CreatorFixture;
    $record->save();

    $page->dispatch('refresh-page')->assertSee('Records: 1');
});
