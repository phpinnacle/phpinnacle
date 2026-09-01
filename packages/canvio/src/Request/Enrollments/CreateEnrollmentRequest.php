<?php

namespace PHPinnacle\Canvio\Request\Enrollments;

use DateTimeInterface;
use InvalidArgumentException;
use PHPinnacle\Canvio\Enum\EnrollmentState;
use PHPinnacle\Canvio\Enum\EnrollmentType;
use PHPinnacle\Canvio\Support\RequestBody;

final readonly class CreateEnrollmentRequest
{
    public function __construct(
        public int|string $userId,
        public EnrollmentType $type = EnrollmentType::Student,
        public ?EnrollmentState $state = null,
        public ?DateTimeInterface $startAt = null,
        public ?DateTimeInterface $endAt = null,
        public ?int $roleId = null,
        public ?int $courseSectionId = null,
        public ?bool $limitPrivilegesToCourseSection = null,
        public ?bool $notify = null,
        public ?string $selfEnrollmentCode = null,
        public ?bool $selfEnrolled = null,
        public ?int $associatedUserId = null,
    ) {
        if (is_int($userId) && $userId < 1 || is_string($userId) && trim($userId) === '') {
            throw new InvalidArgumentException('Enrollment user ID must be a positive integer or non-empty string.');
        }
    }

    /** @return array{enrollment: array<string, mixed>} */
    public function toArray(): array
    {
        return ['enrollment' => RequestBody::withoutNulls([
            'user_id' => $this->userId,
            'type' => $this->type,
            'enrollment_state' => $this->state,
            'start_at' => RequestBody::date($this->startAt),
            'end_at' => RequestBody::date($this->endAt),
            'role_id' => $this->roleId,
            'course_section_id' => $this->courseSectionId,
            'limit_privileges_to_course_section' => $this->limitPrivilegesToCourseSection,
            'notify' => $this->notify,
            'self_enrollment_code' => $this->selfEnrollmentCode,
            'self_enrolled' => $this->selfEnrolled,
            'associated_user_id' => $this->associatedUserId,
        ])];
    }
}
