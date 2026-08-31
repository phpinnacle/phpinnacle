<?php

namespace PHPinnacle\Comments\Resources\Comments;

use Filament\Resources\Resource;
use PHPinnacle\Comments\Models\Comment;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static bool $isScopedToTenant = false;
}
