<?php

use Filament\Facades\Filament;
use Filament\Notifications\DatabaseNotification;
use Filament\Panel;
use Illuminate\Auth\Access\Gate as GateManager;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PHPinnacle\Comments\Livewire\Comments as CommentsComponent;
use PHPinnacle\Comments\Models\Comment;
use PHPinnacle\Comments\Models\CommentSubscription;
use PHPinnacle\Comments\Services\CommentNotifier;
use Tests\TestCase;

uses(TestCase::class);

final class CommentAuthor extends Authenticatable
{
    use Notifiable;

    public $timestamps = false;

    protected $guarded = [];
}

final class CommentSubject extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $keyType = 'string';
}

beforeEach(function () {
    config()->set('phpinnacle-comments.user.model', CommentAuthor::class);

    Schema::create('comment_authors', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });

    Schema::create('comment_subjects', function (Blueprint $table) {
        $table->uuid('id')->primary();
    });

    $migration = require __DIR__ . '/../../database/migrations/create_comments_table.php';
    $migration->up();

    $migration = require __DIR__ . '/../../database/migrations/update_comments_table.php';
    $migration->up();

    Filament::setCurrentPanel(Panel::make()->id('comments-test'));
});

afterEach(function () {
    Filament::setCurrentPanel(null);
});

it('represents a comment and its polymorphic subject', function () {
    $comment = new Comment;
    $comment->forceFill([
        'subject_type' => CommentSubject::class,
        'subject_id' => 'order-1',
        'text' => 'Ready for review',
    ]);

    expect($comment->getTable())
        ->toBe('comments')
        ->and($comment->text)
        ->toBe('Ready for review')
        ->and($comment->subject())
        ->toBeInstanceOf(MorphTo::class);
});

it('lists only subject comments with their authors and quoted parents loaded', function () {
    $author = CommentAuthor::query()->create(['name' => 'Ada']);
    $subject = CommentSubject::query()->create(['id' => '00000000-0000-0000-0000-000000000001']);
    $otherSubject = CommentSubject::query()->create(['id' => '00000000-0000-0000-0000-000000000002']);
    $parent = Comment::query()->create([
        'author_id' => $author->getKey(),
        'subject_type' => $subject->getMorphClass(),
        'subject_id' => $subject->getKey(),
        'text' => 'Parent',
    ]);
    $reply = Comment::query()->create([
        'author_id' => $author->getKey(),
        'subject_type' => $subject->getMorphClass(),
        'subject_id' => $subject->getKey(),
        'parent_id' => $parent->getKey(),
        'text' => 'Reply',
    ]);
    Comment::query()->create([
        'author_id' => $author->getKey(),
        'subject_type' => $otherSubject->getMorphClass(),
        'subject_id' => $otherSubject->getKey(),
        'text' => 'Other thread',
    ]);

    $comments = Comment::list($subject);
    $listedReply = $comments->firstWhere('id', $reply->getKey());

    expect(Comment::count($subject))
        ->toBe(2)
        ->and($comments)
        ->toHaveCount(2)
        ->and($listedReply->relationLoaded('author'))
        ->toBeTrue()
        ->and($listedReply->relationLoaded('parent'))
        ->toBeTrue()
        ->and($listedReply->parent->relationLoaded('author'))
        ->toBeTrue()
        ->and($listedReply->parent())
        ->toBeInstanceOf(BelongsTo::class);
});

it('extracts unique native rich editor mention ids', function () {
    $comment = new Comment;
    $comment->text = <<<'HTML'
        <p>Hello <span data-type="mention" data-id="2" data-label="Ada">@Ada</span>
        and <span data-id="3" data-type="mention" data-label="Grace">@Grace</span>
        and <span data-type="mention" data-id="2" data-label="Ada">@Ada</span>.</p>
        HTML;

    expect($comment->mentionedUserIds())->toBe(['2', '3']);
});

it('keeps subscriptions scoped to their subject', function () {
    $author = CommentAuthor::query()->create(['name' => 'Ada']);
    $subject = CommentSubject::query()->create(['id' => '00000000-0000-0000-0000-000000000001']);
    $otherSubject = CommentSubject::query()->create(['id' => '00000000-0000-0000-0000-000000000002']);

    foreach ([$subject, $otherSubject] as $record) {
        CommentSubscription::query()->create([
            'user_id' => $author->getKey(),
            'subject_type' => $record->getMorphClass(),
            'subject_id' => $record->getKey(),
        ]);
    }

    expect(CommentSubscription::query()->forSubject($subject)->count())->toBe(1);
});

it('rolls back the feature migration', function () {
    $migration = require __DIR__ . '/../../database/migrations/update_comments_table.php';
    $migration->down();

    expect(Schema::hasTable('comment_subscriptions'))
        ->toBeFalse()
        ->and(Schema::hasColumn('comments', 'parent_id'))
        ->toBeFalse()
        ->and(Schema::hasColumn('comments', 'edited_at'))
        ->toBeFalse();
});

it('notifies subscribers and mentioned users once while excluding the author', function () {
    NotificationFacade::fake();

    $author = CommentAuthor::query()->create(['name' => 'Ada']);
    $subscriber = CommentAuthor::query()->create(['name' => 'Grace']);
    $mentioned = CommentAuthor::query()->create(['name' => 'Linus']);
    $subject = CommentSubject::query()->create(['id' => '00000000-0000-0000-0000-000000000001']);
    $comment = Comment::query()->create([
        'author_id' => $author->getKey(),
        'subject_type' => $subject->getMorphClass(),
        'subject_id' => $subject->getKey(),
        'text' => sprintf(
            '<p>Hello <span data-type="mention" data-id="%s" data-label="Linus">@Linus</span></p>',
            $mentioned->getKey(),
        ),
    ]);

    foreach ([$author, $subscriber, $mentioned] as $user) {
        CommentSubscription::query()->create([
            'user_id' => $user->getKey(),
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
        ]);
    }

    app(CommentNotifier::class)->send($comment, $author);

    NotificationFacade::assertNotSentTo($author, DatabaseNotification::class);
    NotificationFacade::assertSentTo(
        $subscriber,
        DatabaseNotification::class,
        fn (DatabaseNotification $notification) => $notification->data['title'] === 'New comment',
    );
    NotificationFacade::assertSentTo(
        $mentioned,
        DatabaseNotification::class,
        fn (DatabaseNotification $notification) => $notification->data['title'] === 'You were mentioned in a comment',
    );
});

it('creates one-level replies and edits authorized comments', function () {
    $author = CommentAuthor::query()->create(['name' => 'Ada']);
    $subject = CommentSubject::query()->create(['id' => '00000000-0000-0000-0000-000000000001']);

    $this->actingAs($author);
    $gate = new GateManager(app(), static fn () => auth()->user());
    app()->instance(GateContract::class, $gate);
    Gate::swap($gate);
    Gate::define('create', static fn (?AuthenticatableContract $user) => $user !== null);
    Gate::define(
        'update',
        static fn (?AuthenticatableContract $user, Comment $comment) => (
            $user?->getAuthIdentifier() === $comment->author_id
        ),
    );
    Gate::define(
        'delete',
        static fn (?AuthenticatableContract $user, Comment $comment) => (
            $user?->getAuthIdentifier() === $comment->author_id
        ),
    );

    $component = Livewire::test(CommentsComponent::class, ['record' => $subject])
        ->set('data.text', '<p>First</p>')
        ->call('create');
    $parent = Comment::query()->sole();

    $component
        ->call('edit', $parent->getKey())
        ->set('data.text', '<p>Edited</p>')
        ->call('update')
        ->call('reply', $parent->getKey())
        ->set('data.text', '<p>Reply</p>')
        ->call('create');

    $parent->refresh();
    $reply = Comment::query()->whereNotNull('parent_id')->sole();

    expect($parent->text)
        ->toBe('<p>Edited</p>')
        ->and($parent->edited_at)
        ->not
        ->toBeNull()
        ->and($reply->parent_id)
        ->toBe($parent->getKey());
});

it('does not expose or execute comment creation for guests', function () {
    $subject = CommentSubject::query()->create(['id' => '00000000-0000-0000-0000-000000000001']);

    Livewire::test(CommentsComponent::class, ['record' => $subject])
        ->assertDontSee('Comment')
        ->set('data.text', '<p>Unauthorized</p>')
        ->call('create');

    expect(Comment::query()->count())->toBe(0);
});
