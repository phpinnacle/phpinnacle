<?php

namespace PHPinnacle\Comments\Infolists;

use Filament\Infolists\Components\ViewEntry;
use Illuminate\Http\Request;
use PHPinnacle\Comments\Models\Comment;

class CommentsEntry extends ViewEntry
{
    private string $layout = 'default';

    public static function getDefaultName(): ?string
    {
        return 'comments';
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function inverse(): self
    {
        $this->layout = 'inverse';

        return $this;
    }

    public function layout(string $layout): self
    {
        $this->layout = $layout;

        return $this;
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('phpinnacle-comments::forms.heading'))
            ->view('phpinnacle-comments::entry')
            ->visible(fn (Request $request) => $request->user()->can('viewAny', Comment::class));
    }
}
