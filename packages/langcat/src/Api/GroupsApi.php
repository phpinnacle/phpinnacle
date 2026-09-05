<?php

namespace PHPinnacle\Langcat\Api;

use PHPinnacle\Langcat\Request\Groups\AssignStudentToGroupRequest;
use PHPinnacle\Langcat\Request\Groups\ChangeStudentAssignTypeRequest;
use PHPinnacle\Langcat\Request\Groups\CreateGroupRequest;
use PHPinnacle\Langcat\Request\Groups\CreateLessonRequest;
use PHPinnacle\Langcat\Request\Groups\ListGradesRequest;
use PHPinnacle\Langcat\Request\Groups\ListGroupsRequest;
use PHPinnacle\Langcat\Request\Groups\ListGroupStudentsRequest;
use PHPinnacle\Langcat\Request\Groups\ListGroupTeachersRequest;
use PHPinnacle\Langcat\Request\Groups\ListLessonAttendanceRequest;
use PHPinnacle\Langcat\Request\Groups\ListLessonsRequest;
use PHPinnacle\Langcat\Request\Groups\SearchGroupsRequest;
use PHPinnacle\Langcat\Request\Groups\UpdateGroupRequest;
use PHPinnacle\Langcat\Request\Groups\UpdateLessonRequest;
use PHPinnacle\Langcat\Response\Groups\GroupResponse;
use PHPinnacle\Langcat\Response\Groups\GroupSearchResultsResponse;
use PHPinnacle\Langcat\Response\Groups\GroupsResponse;
use PHPinnacle\Langcat\Response\Groups\LessonResponse;
use PHPinnacle\Langcat\Response\Groups\LessonsResponse;
use PHPinnacle\Langcat\Response\Groups\ManualGradesResponse;
use PHPinnacle\Langcat\Response\Groups\PredefinedGradesResponse;
use PHPinnacle\Langcat\Response\Groups\StudentAttendancesResponse;
use PHPinnacle\Langcat\Response\Groups\StudentInGroupResponse;
use PHPinnacle\Langcat\Response\Groups\StudentsInGroupResponse;
use PHPinnacle\Langcat\Response\Groups\TeacherInGroupResponse;
use PHPinnacle\Langcat\Response\Groups\TeachersInGroupResponse;
use PHPinnacle\Langcat\Response\Shared\EmptyResponse;
use PHPinnacle\Langcat\Response\Shared\IdResponse;
use PHPinnacle\Langcat\Support\ResourcePath;
use PHPinnacle\Langcat\Support\Transport;
use PHPinnacle\Langcat\Support\TransportMode;

final readonly class GroupsApi
{
    public function __construct(
        private Transport $transport,
    ) {}

    public function all(?ListGroupsRequest $request = null): GroupsResponse
    {
        return GroupsResponse::fromArray($this->transport->send('GET', '/groups', $request?->toQuery() ?? []));
    }

    public function assignStudent(int $groupId, AssignStudentToGroupRequest $request): EmptyResponse
    {
        return EmptyResponse::fromArray($this->transport->send(
            'POST',
            $this->groupPath($groupId) . '/students',
            body: $request->toArray(),
            mode: TransportMode::EmptyResponse,
        ));
    }

    public function changeStudentAssignType(
        int $groupId,
        int $studentId,
        ChangeStudentAssignTypeRequest $request,
    ): EmptyResponse {
        return EmptyResponse::fromArray($this->transport->send(
            'PATCH',
            $this->studentPath($groupId, $studentId) . '/assign-type',
            body: $request->toArray(),
            mode: TransportMode::EmptyResponse,
        ));
    }

    public function create(CreateGroupRequest $request): EmptyResponse
    {
        return EmptyResponse::fromArray($this->transport->send(
            'POST',
            '/groups',
            body: $request->toArray(),
            mode: TransportMode::EmptyResponse,
        ));
    }

    public function createLesson(int $groupId, CreateLessonRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send(
            'POST',
            $this->groupPath($groupId) . '/lessons',
            body: $request->toArray(),
        ));
    }

    public function get(int $groupId): GroupResponse
    {
        return GroupResponse::fromArray($this->transport->send('GET', $this->groupPath($groupId)));
    }

    public function lesson(int $groupId, int $lessonId): LessonResponse
    {
        return LessonResponse::fromArray($this->transport->send('GET', $this->lessonPath($groupId, $lessonId)));
    }

    public function lessonAttendance(
        int $groupId,
        int $lessonId,
        ?ListLessonAttendanceRequest $request = null,
    ): StudentAttendancesResponse {
        return StudentAttendancesResponse::fromArray($this->transport->send(
            'GET',
            $this->lessonPath($groupId, $lessonId) . '/attendance',
            $request?->toQuery() ?? [],
        ));
    }

    public function lessons(int $groupId, ?ListLessonsRequest $request = null): LessonsResponse
    {
        return LessonsResponse::fromArray($this->transport->send(
            'GET',
            $this->groupPath($groupId) . '/lessons',
            $request?->toQuery() ?? [],
        ));
    }

    public function manualGrades(
        int $groupId,
        int $studentId,
        ?ListGradesRequest $request = null,
    ): ManualGradesResponse {
        return ManualGradesResponse::fromArray($this->transport->send(
            'GET',
            $this->studentPath($groupId, $studentId) . '/grades',
            $request?->toQuery() ?? [],
        ));
    }

    public function predefinedGrades(
        int $groupId,
        int $studentId,
        ?ListGradesRequest $request = null,
    ): PredefinedGradesResponse {
        return PredefinedGradesResponse::fromArray($this->transport->send(
            'GET',
            $this->studentPath($groupId, $studentId) . '/grades/collections',
            $request?->toQuery() ?? [],
        ));
    }

    public function search(?SearchGroupsRequest $request = null): GroupSearchResultsResponse
    {
        return GroupSearchResultsResponse::fromArray($this->transport->send(
            'GET',
            '/groups/search',
            $request?->toQuery() ?? [],
        ));
    }

    public function student(int $groupId, int $studentId): StudentInGroupResponse
    {
        return StudentInGroupResponse::fromArray($this->transport->send('GET', $this->studentPath(
            $groupId,
            $studentId,
        )));
    }

    public function students(int $groupId, ?ListGroupStudentsRequest $request = null): StudentsInGroupResponse
    {
        return StudentsInGroupResponse::fromArray($this->transport->send(
            'GET',
            $this->groupPath($groupId) . '/students',
            $request?->toQuery() ?? [],
        ));
    }

    public function teacher(int $groupId, int $teacherId): TeacherInGroupResponse
    {
        return TeacherInGroupResponse::fromArray($this->transport->send(
            'GET',
            ResourcePath::id(ltrim($this->groupPath($groupId), '/') . '/teachers', $teacherId),
        ));
    }

    public function teachers(int $groupId, ?ListGroupTeachersRequest $request = null): TeachersInGroupResponse
    {
        return TeachersInGroupResponse::fromArray($this->transport->send(
            'GET',
            $this->groupPath($groupId) . '/teachers',
            $request?->toQuery() ?? [],
        ));
    }

    public function update(int $groupId, UpdateGroupRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send(
            'PATCH',
            $this->groupPath($groupId),
            body: $request->toArray(),
        ));
    }

    public function updateLesson(int $groupId, int $lessonId, UpdateLessonRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send(
            'PATCH',
            $this->lessonPath($groupId, $lessonId),
            body: $request->toArray(),
        ));
    }

    private function groupPath(int $groupId): string
    {
        return ResourcePath::id('groups', $groupId);
    }

    private function lessonPath(int $groupId, int $lessonId): string
    {
        return ResourcePath::id(ltrim($this->groupPath($groupId), '/') . '/lessons', $lessonId);
    }

    private function studentPath(int $groupId, int $studentId): string
    {
        return ResourcePath::id(ltrim($this->groupPath($groupId), '/') . '/students', $studentId);
    }
}
