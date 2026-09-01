<?php

namespace PHPinnacle\Canvio\Request\Submissions;

use LogicException;
use PHPinnacle\Canvio\Enum\LatePolicyStatus;
use PHPinnacle\Canvio\Enum\MediaCommentType;
use PHPinnacle\Canvio\Support\RequestBody;

final readonly class UpdateSubmissionRequest
{
    /** @param list<int>|null $commentFileIds */
    public function __construct(
        public string|int|float|null $postedGrade = null,
        public ?bool $excused = null,
        public ?LatePolicyStatus $latePolicyStatus = null,
        public ?int $secondsLateOverride = null,
        public ?bool $peerReview = null,
        public ?string $textComment = null,
        public ?bool $groupComment = null,
        public ?array $commentFileIds = null,
        public ?string $mediaCommentId = null,
        public ?MediaCommentType $mediaCommentType = null,
        public ?bool $preferPointsOverScheme = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $submission = RequestBody::withoutNulls([
            'posted_grade' => $this->postedGrade,
            'excuse' => $this->excused,
            'late_policy_status' => $this->latePolicyStatus,
            'seconds_late_override' => $this->secondsLateOverride,
            'peer_review' => $this->peerReview,
        ]);
        $comment = RequestBody::withoutNulls([
            'text_comment' => $this->textComment,
            'group_comment' => $this->groupComment,
            'file_ids' => $this->commentFileIds,
            'media_comment_id' => $this->mediaCommentId,
            'media_comment_type' => $this->mediaCommentType,
        ]);
        $request = RequestBody::withoutNulls([
            'submission' => $submission === [] ? null : $submission,
            'comment' => $comment === [] ? null : $comment,
            'prefer_points_over_scheme' => $this->preferPointsOverScheme,
        ]);

        if ($request === []) {
            throw new LogicException('At least one submission field must be updated.');
        }

        return $request;
    }
}
