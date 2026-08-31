<?php

namespace PHPinnacle\Comments\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use PHPinnacle\Comments\Models\Comment;

/**
 * @property-read Schema $form
 */
class Comments extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?array $data = [];

    public Model $record;

    public string $layout = 'default';

    public function create(): void
    {
        $user = Filament::auth()->user();

        if ($user->cant('create', Comment::class)) {
            return;
        }

        $this->form->validate();

        $data = $this->form->getState();

        $comment = new Comment;
        $comment->author_id = $user->getAuthIdentifier();
        $comment->subject_type = $this->record->getMorphClass();
        $comment->subject_id = $this->record->getKey();
        $comment->text = $data['text'];
        $comment->save();

        Notification::make()
            ->title(__('phpinnacle-comments::notifications.comment.created'))
            ->success()
            ->send();

        $this->form->fill();
    }

    public function delete(string $id): void
    {
        if (!($comment = Comment::query()->find($id))) {
            return;
        }

        $user = Filament::auth()->user();

        if ($user->cant('delete', $comment)) {
            return;
        }

        $comment->delete();

        Notification::make()
            ->title(__('phpinnacle-comments::notifications.comment.deleted'))
            ->success()
            ->send();
    }

    public function form(Schema $schema): Schema
    {
        $user = Filament::auth()->user();

        if ($user->cant('create', Comment::class)) {
            return $schema;
        }

        return $schema
            ->components([
                RichEditor::make('text')
                    ->hiddenLabel()
                    ->toolbarButtons(config('phpinnacle-comments.toolbar'))
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
        return view('phpinnacle-comments::livewire.comments', [
            'comments' => Comment::list($this->record),
            'layout' => $this->layout,
            'user' => Filament::auth()->user(),
        ]);
    }
}
