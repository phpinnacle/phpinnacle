<?php

namespace PHPinnacle\Canvio\Request\Submissions;

use DateTimeInterface;
use PHPinnacle\Canvio\Enum\MediaCommentType;
use PHPinnacle\Canvio\Enum\SubmissionType;
use PHPinnacle\Canvio\Support\RequestBody;

final readonly class SubmitSubmissionRequest
{
    /** @param list<int>|null $fileIds */
    public function __construct(
        public SubmissionType $type,
        public ?string $body = null,
        public ?string $url = null,
        public ?array $fileIds = null,
        public ?string $mediaCommentId = null,
        public ?MediaCommentType $mediaCommentType = null,
        public ?int $userId = null,
        public ?int $annotatableAttachmentId = null,
        public ?DateTimeInterface $submittedAt = null,
        public ?bool $groupComment = null,
        public ?string $textComment = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $request = [
            'submission' => RequestBody::withoutNulls([
                'submission_type' => $this->type,
                'body' => $this->body,
                'url' => $this->url,
                'file_ids' => $this->fileIds,
                'media_comment_id' => $this->mediaCommentId,
                'media_comment_type' => $this->mediaCommentType,
                'user_id' => $this->userId,
                'annotatable_attachment_id' => $this->annotatableAttachmentId,
                'submitted_at' => RequestBody::date($this->submittedAt),
                'group_comment' => $this->groupComment,
            ]),
        ];

        if ($this->textComment !== null) {
            $request['comment'] = ['text_comment' => $this->textComment];
        }

        return $request;
    }
}
