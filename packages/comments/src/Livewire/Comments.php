<?php

namespace PHPinnacle\Comments\Livewire;

use Carbon\CarbonImmutable;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use PHPinnacle\Comments\Models\Comment;
use PHPinnacle\Comments\Models\CommentSubscription;
use PHPinnacle\Comments\Services\CommentNotifier;

/**
 * @property-read Schema $form
 */
class Comments extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    /**
     * @var array{text?: string|null}|null
     */
    public ?array $data = [];

    public ?string $editing = null;

    public Model $record;

    public ?string $replying = null;

    public string $layout = 'default';

    public function cancel(): void
    {
        $this->resetComposer();
    }

    public function create(): void
    {
        if (($user = $this->authorizedUser('create', Comment::class)) === null) {
            return;
        }

        $this->form->validate();

        $parent = $this->replying === null
            ? null
            : $this->commentsQuery()->whereNull('parent_id')->find($this->replying);

        if ($this->replying !== null && $parent === null) {
            throw ValidationException::withMessages([
                'data.text' => __('phpinnacle-comments::forms.invalid_reply'),
            ]);
        }

        /** @var array{text: string} $data */
        $data = $this->form->getState();

        $comment = new Comment([
            'author_id' => $user->getAuthIdentifier(),
            'subject_type' => $this->record->getMorphClass(),
            'subject_id' => $this->record->getKey(),
            'parent_id' => $parent?->id,
            'text' => $data['text'],
        ]);
        $comment->save();

        app(CommentNotifier::class)->send($comment, $user);

        Notification::make()
            ->title(__('phpinnacle-comments::notifications.comment.created'))
            ->success()
            ->send();

        $this->resetComposer();
    }

    public function delete(string $id): void
    {
        if (!($comment = $this->commentsQuery()->find($id))) {
            return;
        }

        if ($this->authorizedUser('delete', $comment) === null) {
            return;
        }

        $comment->delete();

        if ($this->editing === $id || $this->replying === $id) {
            $this->resetComposer();
        }

        Notification::make()
            ->title(__('phpinnacle-comments::notifications.comment.deleted'))
            ->success()
            ->send();
    }

    public function edit(string $id): void
    {
        $comment = $this->commentsQuery()->find($id);

        if ($comment === null || $this->authorizedUser('update', $comment) === null) {
            return;
        }

        $this->editing = $id;
        $this->replying = null;
        $this->form->fill(['text' => $comment->text]);
    }

    public function form(Schema $schema): Schema
    {
        if ($this->editing === null && $this->authorizedUser('create', Comment::class) === null) {
            return $schema;
        }

        /** @var array<int, array<int, object|string>|object|string>|\Closure|null $toolbar */
        $toolbar = config('phpinnacle-comments.toolbar');

        return $schema
            ->components([
                RichEditor::make('text')
                    ->hiddenLabel()
                    ->mentions([app(CommentNotifier::class)->mentionProvider()])
                    ->toolbarButtons($toolbar)
                    ->extraInputAttributes([
                        'class' => '!min-h-2',
                    ])
                    ->required(),
            ])
            ->statePath('data');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function render(): View
    {
        $comments = Comment::list($this->record);
        $user = Filament::auth()->user();

        return view('phpinnacle-comments::livewire.comments', [
            'canCreate' => $this->authorizedUser('create', Comment::class) !== null,
            'comments' => $comments,
            'editingComment' => $comments->firstWhere('id', $this->editing),
            'layout' => $this->layout,
            'mentionProvider' => app(CommentNotifier::class)->mentionProvider(),
            'replyingTo' => $comments->firstWhere('id', $this->replying),
            'subscribed' => $user !== null && CommentSubscription::forSubject($this->record)
                ->where('user_id', $user->getAuthIdentifier())
                ->exists(),
            'user' => $user,
        ]);
    }

    public function reply(string $id): void
    {
        if ($this->authorizedUser('create', Comment::class) === null) {
            return;
        }

        if (($comment = $this->commentsQuery()->find($id)) === null) {
            return;
        }

        $this->editing = null;
        $this->replying = $comment->parent_id ?? $comment->id;
        $this->form->fill();
    }

    public function toggleSubscription(): void
    {
        if (($user = Filament::auth()->user()) === null) {
            return;
        }

        $subscription = CommentSubscription::forSubject($this->record)
            ->where('user_id', $user->getAuthIdentifier());

        if ($subscription->exists()) {
            $subscription->delete();

            return;
        }

        CommentSubscription::query()->create([
            'user_id' => $user->getAuthIdentifier(),
            'subject_type' => $this->record->getMorphClass(),
            'subject_id' => $this->record->getKey(),
        ]);
    }

    public function update(): void
    {
        if ($this->editing === null || ($comment = $this->commentsQuery()->find($this->editing)) === null) {
            return;
        }

        if ($this->authorizedUser('update', $comment) === null) {
            return;
        }

        $this->form->validate();

        /** @var array{text: string} $data */
        $data = $this->form->getState();

        $comment->text = $data['text'];
        $comment->edited_at = CarbonImmutable::now();
        $comment->save();

        Notification::make()
            ->title(__('phpinnacle-comments::notifications.comment.updated'))
            ->success()
            ->send();

        $this->resetComposer();
    }

    private function authorizedUser(string $ability, mixed $arguments): ?Authenticatable
    {
        $user = Filament::auth()->user();

        if ($user === null || Gate::forUser($user)->denies($ability, $arguments)) {
            return null;
        }

        return $user;
    }

    /** @return Builder<Comment> */
    private function commentsQuery(): Builder
    {
        return Comment::forSubject($this->record);
    }

    private function resetComposer(): void
    {
        $this->editing = null;
        $this->replying = null;
        $this->form->fill();
    }
}
