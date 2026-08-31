<?php

namespace PHPinnacle\Comments\Actions;

use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class CommentsAction extends Action
{
    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-comments::forms.label'))
            ->hiddenLabel()
            ->name('phpinnacle_comments')
            ->icon('phosphor-chats-circle')
            ->color('gray')
            ->button()
            ->slideOver()
            ->modalContentFooter(fn (Model $record) => view('phpinnacle-comments::index', [
                'record' => $record,
            ]))
            ->modalHeading(__('phpinnacle-comments::forms.heading'))
            ->modalWidth(Width::ExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->visible(fn (Model $record) => Gate::allows('comment', $record));
    }
}
