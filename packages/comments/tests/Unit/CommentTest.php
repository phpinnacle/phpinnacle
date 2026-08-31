<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use PHPinnacle\Comments\Models\Comment;
use Tests\TestCase;

uses(TestCase::class);

final class CommentSubject extends Model {}

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
