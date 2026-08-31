<?php

namespace PHPinnacle\Langcat\Api;

use InvalidArgumentException;
use PHPinnacle\Langcat\Request\Shared\ListConsentsRequest;
use PHPinnacle\Langcat\Request\Shared\UpdateDetailsRequest;
use PHPinnacle\Langcat\Request\Students\AssignStudentToSchoolRequest;
use PHPinnacle\Langcat\Request\Students\CreateStudentRequest;
use PHPinnacle\Langcat\Request\Students\ListStudentsRequest;
use PHPinnacle\Langcat\Request\Students\SearchStudentsRequest;
use PHPinnacle\Langcat\Request\Students\UpdateParentRequest;
use PHPinnacle\Langcat\Request\Students\UpdateStudentRequest;
use PHPinnacle\Langcat\Response\Shared\AccessResponse;
use PHPinnacle\Langcat\Response\Shared\ConsentsResponse;
use PHPinnacle\Langcat\Response\Shared\DetailsResponse;
use PHPinnacle\Langcat\Response\Shared\EmptyResponse;
use PHPinnacle\Langcat\Response\Shared\IdResponse;
use PHPinnacle\Langcat\Response\Students\AdvancedStudentsResponse;
use PHPinnacle\Langcat\Response\Students\ParentResponse;
use PHPinnacle\Langcat\Response\Students\ParentsResponse;
use PHPinnacle\Langcat\Response\Students\StudentResponse;
use PHPinnacle\Langcat\Response\Students\StudentsResponse;
use PHPinnacle\Langcat\Support\Transport;

final readonly class StudentsApi
{
    public function __construct(
        private Transport $transport,
    ) {}

    public function access(int $studentId): AccessResponse
    {
        return AccessResponse::fromArray($this->transport->send('GET', $this->studentPath($studentId) . '/access'));
    }

    public function all(?ListStudentsRequest $request = null): StudentsResponse
    {
        return StudentsResponse::fromArray($this->transport->send('GET', '/students', $request?->toQuery() ?? []));
    }

    public function archive(int $studentId): EmptyResponse
    {
        return EmptyResponse::fromArray($this->transport->send(
            'POST',
            $this->studentPath($studentId) . '/archive',
            allowEmptyResponse: true,
        ));
    }

    public function assignToSchool(int $studentId, AssignStudentToSchoolRequest $request): EmptyResponse
    {
        return EmptyResponse::fromArray($this->transport->send(
            'POST',
            $this->studentPath($studentId) . '/schools',
            body: $request->toArray(),
            allowEmptyResponse: true,
        ));
    }

    public function consents(int $studentId, ?ListConsentsRequest $request = null): ConsentsResponse
    {
        return ConsentsResponse::fromArray($this->transport->send(
            'GET',
            $this->studentPath($studentId) . '/consents',
            $request?->toQuery() ?? [],
        ));
    }

    public function create(CreateStudentRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send('POST', '/students', body: $request->toArray()));
    }

    public function delete(int $studentId): EmptyResponse
    {
        return EmptyResponse::fromArray($this->transport->send(
            'DELETE',
            $this->studentPath($studentId),
            allowEmptyResponse: true,
        ));
    }

    public function details(int $studentId): DetailsResponse
    {
        return DetailsResponse::fromArray($this->transport->send('GET', $this->studentPath($studentId) . '/details'));
    }

    public function get(int $studentId): StudentResponse
    {
        return StudentResponse::fromArray($this->transport->send('GET', $this->studentPath($studentId)));
    }

    public function parent(int $studentId, int $parentId): ParentResponse
    {
        return ParentResponse::fromArray($this->transport->send('GET', $this->parentPath($studentId, $parentId)));
    }

    public function parentAccess(int $studentId, int $parentId): AccessResponse
    {
        return AccessResponse::fromArray($this->transport->send(
            'GET',
            $this->parentPath($studentId, $parentId) . '/access',
        ));
    }

    public function parentConsents(
        int $studentId,
        int $parentId,
        ?ListConsentsRequest $request = null,
    ): ConsentsResponse {
        return ConsentsResponse::fromArray($this->transport->send(
            'GET',
            $this->parentPath($studentId, $parentId) . '/consents',
            $request?->toQuery() ?? [],
        ));
    }

    public function parentDetails(int $studentId, int $parentId): DetailsResponse
    {
        return DetailsResponse::fromArray($this->transport->send(
            'GET',
            $this->parentPath($studentId, $parentId) . '/details',
        ));
    }

    public function parents(int $studentId): ParentsResponse
    {
        return ParentsResponse::fromArray($this->transport->send('GET', $this->studentPath($studentId) . '/parents'));
    }

    public function restore(int $studentId): EmptyResponse
    {
        return EmptyResponse::fromArray($this->transport->send(
            'POST',
            $this->studentPath($studentId) . '/restore',
            allowEmptyResponse: true,
        ));
    }

    public function search(SearchStudentsRequest $request): AdvancedStudentsResponse
    {
        return AdvancedStudentsResponse::fromArray($this->transport->send(
            'GET',
            '/students/search',
            $request->toQuery(),
        ));
    }

    public function update(int $studentId, UpdateStudentRequest $request): EmptyResponse
    {
        return EmptyResponse::fromArray($this->transport->send(
            'PATCH',
            $this->studentPath($studentId),
            body: $request->toArray(),
            allowEmptyResponse: true,
        ));
    }

    public function updateDetails(int $studentId, UpdateDetailsRequest $request): EmptyResponse
    {
        return EmptyResponse::fromArray($this->transport->send(
            'PATCH',
            $this->studentPath($studentId) . '/details',
            body: $request->toArray(),
            allowEmptyResponse: true,
        ));
    }

    public function updateParent(int $studentId, int $parentId, UpdateParentRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send(
            'PATCH',
            $this->parentPath($studentId, $parentId),
            body: $request->toArray(),
        ));
    }

    public function updateParentDetails(
        int $studentId,
        int $parentId,
        UpdateDetailsRequest $request,
    ): EmptyResponse {
        return EmptyResponse::fromArray($this->transport->send(
            'PATCH',
            $this->parentPath($studentId, $parentId) . '/details',
            body: $request->toArray(),
            allowEmptyResponse: true,
        ));
    }

    private function parentPath(int $studentId, int $parentId): string
    {
        if ($parentId < 1) {
            throw new InvalidArgumentException('Parent ID must be positive.');
        }

        return $this->studentPath($studentId) . '/parents/' . $parentId;
    }

    private function studentPath(int $studentId): string
    {
        if ($studentId < 1) {
            throw new InvalidArgumentException('Student ID must be positive.');
        }

        return '/students/' . $studentId;
    }
}
