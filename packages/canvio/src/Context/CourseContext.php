<?php

namespace PHPinnacle\Canvio\Context;

use Generator;
use PHPinnacle\Canvio\Request\Assignments\CreateAssignmentRequest;
use PHPinnacle\Canvio\Request\Assignments\ListAssignmentsRequest;
use PHPinnacle\Canvio\Request\Enrollments\CreateEnrollmentRequest;
use PHPinnacle\Canvio\Request\Enrollments\ListEnrollmentsRequest;
use PHPinnacle\Canvio\Request\Users\ListCourseUsersRequest;
use PHPinnacle\Canvio\Response\AssignmentResponse;
use PHPinnacle\Canvio\Response\CourseResponse;
use PHPinnacle\Canvio\Response\EnrollmentResponse;
use PHPinnacle\Canvio\Response\UserResponse;
use PHPinnacle\Canvio\Support\ResourcePath;
use PHPinnacle\Canvio\Support\Transport;

final readonly class CourseContext
{
    private string $path;

    public function __construct(
        private Transport $transport,
        private int|string $courseId,
    ) {
        $this->path = '/courses/' . ResourcePath::segment($courseId, 'Course ID');
    }

    public function assignment(int|string $assignmentId): AssignmentContext
    {
        return new AssignmentContext($this->transport, $this->courseId, $assignmentId);
    }

    /** @return Generator<int, AssignmentResponse> */
    public function assignments(?ListAssignmentsRequest $request = null): Generator
    {
        foreach ($this->transport->paginate($this->path . '/assignments', $request?->toQuery() ?? []) as $payload) {
            yield AssignmentResponse::fromArray($payload);
        }
    }

    public function createAssignment(CreateAssignmentRequest $request): AssignmentResponse
    {
        return AssignmentResponse::fromArray($this->transport->send(
            'POST',
            $this->path . '/assignments',
            body: $request->toArray(),
        ));
    }

    public function details(): CourseResponse
    {
        return CourseResponse::fromArray($this->transport->send('GET', $this->path));
    }

    public function enroll(CreateEnrollmentRequest $request): EnrollmentResponse
    {
        return EnrollmentResponse::fromArray($this->transport->send(
            'POST',
            $this->path . '/enrollments',
            body: $request->toArray(),
        ));
    }

    public function enrollment(int|string $enrollmentId): EnrollmentContext
    {
        return new EnrollmentContext($this->transport, $this->courseId, $enrollmentId);
    }

    /** @return Generator<int, EnrollmentResponse> */
    public function enrollments(?ListEnrollmentsRequest $request = null): Generator
    {
        foreach ($this->transport->paginate($this->path . '/enrollments', $request?->toQuery() ?? []) as $payload) {
            yield EnrollmentResponse::fromArray($payload);
        }
    }

    /** @return Generator<int, UserResponse> */
    public function users(?ListCourseUsersRequest $request = null): Generator
    {
        foreach ($this->transport->paginate($this->path . '/users', $request?->toQuery() ?? []) as $payload) {
            yield UserResponse::fromArray($payload);
        }
    }
}
