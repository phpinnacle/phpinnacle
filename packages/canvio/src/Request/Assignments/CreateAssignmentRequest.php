<?php

namespace PHPinnacle\Canvio\Request\Assignments;

use DateTimeInterface;
use InvalidArgumentException;
use PHPinnacle\Canvio\Enum\AssignmentGradingType;
use PHPinnacle\Canvio\Enum\AssignmentSubmissionType;
use PHPinnacle\Canvio\Support\RequestBody;

final readonly class CreateAssignmentRequest
{
    /**
     * @param  list<AssignmentSubmissionType>|null  $submissionTypes
     * @param  list<string>|null  $allowedExtensions
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public int|float|null $pointsPossible = null,
        public ?AssignmentGradingType $gradingType = null,
        public ?array $submissionTypes = null,
        public ?array $allowedExtensions = null,
        public ?int $assignmentGroupId = null,
        public ?DateTimeInterface $dueAt = null,
        public ?DateTimeInterface $unlockAt = null,
        public ?DateTimeInterface $lockAt = null,
        public ?bool $published = null,
        public ?bool $onlyVisibleToOverrides = null,
        public ?bool $peerReviews = null,
        public ?bool $automaticPeerReviews = null,
        public ?bool $notifyOfUpdate = null,
        public ?int $position = null,
        public ?bool $omitFromFinalGrade = null,
    ) {
        if (trim($name) === '') {
            throw new InvalidArgumentException('Assignment name cannot be empty.');
        }

        if ($pointsPossible !== null && $pointsPossible < 0) {
            throw new InvalidArgumentException('Assignment points possible cannot be negative.');
        }
    }

    /** @return array{assignment: array<string, mixed>} */
    public function toArray(): array
    {
        return ['assignment' => RequestBody::withoutNulls([
            'name' => $this->name,
            'description' => $this->description,
            'points_possible' => $this->pointsPossible,
            'grading_type' => $this->gradingType,
            'submission_types' => $this->submissionTypes,
            'allowed_extensions' => $this->allowedExtensions,
            'assignment_group_id' => $this->assignmentGroupId,
            'due_at' => RequestBody::date($this->dueAt),
            'unlock_at' => RequestBody::date($this->unlockAt),
            'lock_at' => RequestBody::date($this->lockAt),
            'published' => $this->published,
            'only_visible_to_overrides' => $this->onlyVisibleToOverrides,
            'peer_reviews' => $this->peerReviews,
            'automatic_peer_reviews' => $this->automaticPeerReviews,
            'notify_of_update' => $this->notifyOfUpdate,
            'position' => $this->position,
            'omit_from_final_grade' => $this->omitFromFinalGrade,
        ])];
    }
}
