<?php

namespace PHPinnacle\Comments\Services;

use Filament\Forms\Components\RichEditor\MentionProvider;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use PHPinnacle\Comments\Models\Comment;
use PHPinnacle\Comments\Models\CommentSubscription;

class CommentNotifier
{
    public function mentionProvider(): MentionProvider
    {
        /** @var class-string<Model> $userModel */
        $userModel = config('phpinnacle-comments.user.model');

        return MentionProvider::make('@')
            ->getSearchResultsUsing(
                static fn (string $search) => $userModel::query()
                    ->where('name', 'like', "%{$search}%")
                    ->limit(10)
                    ->pluck('name', 'id')
                    ->all(),
            )
            ->getLabelsUsing(
                static fn (array $ids) => $userModel::query()
                    ->whereKey($ids)
                    ->pluck('name', 'id')
                    ->all(),
            );
    }

    public function send(Comment $comment, Authenticatable $author): void
    {
        $mentionedIds = $comment->mentionedUserIds();
        $recipientIds = CommentSubscription::query()
            ->where([
                'subject_type' => $comment->subject_type,
                'subject_id' => $comment->subject_id,
            ])
            ->pluck('user_id')
            ->merge($mentionedIds)
            ->reject(static fn (mixed $id) => (string) $id === (string) $author->getAuthIdentifier())
            ->unique()
            ->values();

        if ($recipientIds->isEmpty()) {
            return;
        }

        /** @var class-string<Model> $userModel */
        $userModel = config('phpinnacle-comments.user.model');
        $users = $userModel::query()->whereKey($recipientIds)->get();
        [$mentioned, $subscribed] = $users->partition(
            static fn (Model $user) => in_array((string) $user->getKey(), $mentionedIds, strict: true),
        );
        $body = Str::limit(trim(strip_tags($comment->text)), 120);

        if ($mentioned->isNotEmpty()) {
            Notification::make()
                ->title(__('phpinnacle-comments::notifications.comment.mentioned'))
                ->body($body)
                ->sendToDatabase($mentioned);
        }

        if ($subscribed->isNotEmpty()) {
            Notification::make()
                ->title(__('phpinnacle-comments::notifications.comment.new'))
                ->body($body)
                ->sendToDatabase($subscribed);
        }
    }
}
