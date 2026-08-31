<?php

namespace PHPinnacle\Langcat\Api;

use PHPinnacle\Langcat\Request\GroupSettings\CreateGroupSettingRequest;
use PHPinnacle\Langcat\Request\GroupSettings\CreateProgramRequest;
use PHPinnacle\Langcat\Request\GroupSettings\ListCourseBooksRequest;
use PHPinnacle\Langcat\Request\GroupSettings\ListCoursesRequest;
use PHPinnacle\Langcat\Request\GroupSettings\ListGradeCollectionItemsRequest;
use PHPinnacle\Langcat\Request\GroupSettings\ListProgramItemsRequest;
use PHPinnacle\Langcat\Request\GroupSettings\ListProgramsRequest;
use PHPinnacle\Langcat\Request\GroupSettings\ListSettingsRequest;
use PHPinnacle\Langcat\Request\GroupSettings\SetProgramItemOrdersRequest;
use PHPinnacle\Langcat\Request\GroupSettings\UpdateGroupSettingRequest;
use PHPinnacle\Langcat\Request\GroupSettings\UpdateProgramRequest;
use PHPinnacle\Langcat\Request\GroupSettings\UpsertProgramItemRequest;
use PHPinnacle\Langcat\Response\GroupSettings\AgeGroupResponse;
use PHPinnacle\Langcat\Response\GroupSettings\AgeGroupsResponse;
use PHPinnacle\Langcat\Response\GroupSettings\AttendanceStatusesResponse;
use PHPinnacle\Langcat\Response\GroupSettings\AttendanceStatusResponse;
use PHPinnacle\Langcat\Response\GroupSettings\CourseBookResponse;
use PHPinnacle\Langcat\Response\GroupSettings\CourseBooksResponse;
use PHPinnacle\Langcat\Response\GroupSettings\CourseResponse;
use PHPinnacle\Langcat\Response\GroupSettings\CoursesResponse;
use PHPinnacle\Langcat\Response\GroupSettings\GradeCollectionItemResponse;
use PHPinnacle\Langcat\Response\GroupSettings\GradeCollectionItemsResponse;
use PHPinnacle\Langcat\Response\GroupSettings\GradeCollectionResponse;
use PHPinnacle\Langcat\Response\GroupSettings\GradeCollectionsResponse;
use PHPinnacle\Langcat\Response\GroupSettings\GroupSettingResponse;
use PHPinnacle\Langcat\Response\GroupSettings\GroupSettingsResponse;
use PHPinnacle\Langcat\Response\GroupSettings\GroupTypeSettingResponse;
use PHPinnacle\Langcat\Response\GroupSettings\GroupTypeSettingsResponse;
use PHPinnacle\Langcat\Response\GroupSettings\LanguageResponse;
use PHPinnacle\Langcat\Response\GroupSettings\LanguagesResponse;
use PHPinnacle\Langcat\Response\GroupSettings\LessonDetailResponse;
use PHPinnacle\Langcat\Response\GroupSettings\LessonDetailsResponse;
use PHPinnacle\Langcat\Response\GroupSettings\LessonStatusesResponse;
use PHPinnacle\Langcat\Response\GroupSettings\LessonStatusResponse;
use PHPinnacle\Langcat\Response\GroupSettings\LevelResponse;
use PHPinnacle\Langcat\Response\GroupSettings\LevelsResponse;
use PHPinnacle\Langcat\Response\GroupSettings\ProgramItemResponse;
use PHPinnacle\Langcat\Response\GroupSettings\ProgramItemsResponse;
use PHPinnacle\Langcat\Response\GroupSettings\ProgramResponse;
use PHPinnacle\Langcat\Response\GroupSettings\ProgramsResponse;
use PHPinnacle\Langcat\Response\GroupSettings\SubjectResponse;
use PHPinnacle\Langcat\Response\GroupSettings\SubjectsResponse;
use PHPinnacle\Langcat\Response\Shared\EmptyResponse;
use PHPinnacle\Langcat\Response\Shared\IdResponse;
use PHPinnacle\Langcat\Support\ResourcePath;
use PHPinnacle\Langcat\Support\Transport;

final readonly class GroupSettingsApi
{
    private const string ROOT = '/groups/settings';

    public function __construct(
        private Transport $transport,
    ) {}

    public function ageGroup(int $id): AgeGroupResponse
    {
        return AgeGroupResponse::fromArray($this->getById('/age-groups', $id));
    }

    public function ageGroups(?ListSettingsRequest $request = null): AgeGroupsResponse
    {
        return AgeGroupsResponse::fromArray($this->get('/age-groups', $request?->toQuery() ?? []));
    }

    public function attendanceStatus(int $id): AttendanceStatusResponse
    {
        return AttendanceStatusResponse::fromArray($this->getById('/attendance-statuses', $id));
    }

    public function attendanceStatuses(?ListSettingsRequest $request = null): AttendanceStatusesResponse
    {
        return AttendanceStatusesResponse::fromArray($this->get('/attendance-statuses', $request?->toQuery() ?? []));
    }

    public function course(int $id): CourseResponse
    {
        return CourseResponse::fromArray($this->getById('/courses', $id));
    }

    public function courseBook(int $id): CourseBookResponse
    {
        return CourseBookResponse::fromArray($this->getById('/course-books', $id));
    }

    public function courseBooks(?ListCourseBooksRequest $request = null): CourseBooksResponse
    {
        return CourseBooksResponse::fromArray($this->get('/course-books', $request?->toQuery() ?? []));
    }

    public function courses(?ListCoursesRequest $request = null): CoursesResponse
    {
        return CoursesResponse::fromArray($this->get('/courses', $request?->toQuery() ?? []));
    }

    public function createProgram(CreateProgramRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send(
            'POST',
            self::ROOT . '/programs/collections',
            body: $request->toArray(),
        ));
    }

    public function createProgramItem(int $programId, UpsertProgramItemRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send(
            'POST',
            $this->programItemsPath($programId),
            body: $request->toArray(),
        ));
    }

    public function createSetting(int $groupId, CreateGroupSettingRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send(
            'POST',
            $this->settingsPath($groupId),
            body: $request->toArray(),
        ));
    }

    public function deleteProgram(int $id): EmptyResponse
    {
        return EmptyResponse::fromArray($this->transport->send('DELETE', $this->pathById(
            '/programs/collections',
            $id,
        )));
    }

    public function deleteProgramItem(int $programId, int $itemId): EmptyResponse
    {
        return EmptyResponse::fromArray($this->transport->send('DELETE', $this->programItemPath($programId, $itemId)));
    }

    public function gradeCollection(int $id): GradeCollectionResponse
    {
        return GradeCollectionResponse::fromArray($this->getById('/grades/collections', $id));
    }

    public function gradeCollectionItem(int $collectionId, int $id): GradeCollectionItemResponse
    {
        return GradeCollectionItemResponse::fromArray($this->transport->send('GET', ResourcePath::id(
            ltrim($this->gradeItemsPath($collectionId), '/'),
            $id,
        )));
    }

    public function gradeCollectionItems(
        int $collectionId,
        ?ListGradeCollectionItemsRequest $request = null,
    ): GradeCollectionItemsResponse {
        return GradeCollectionItemsResponse::fromArray($this->transport->send(
            'GET',
            $this->gradeItemsPath($collectionId),
            $request?->toQuery() ?? [],
        ));
    }

    public function gradeCollections(?ListSettingsRequest $request = null): GradeCollectionsResponse
    {
        return GradeCollectionsResponse::fromArray($this->get('/grades/collections', $request?->toQuery() ?? []));
    }

    public function groupType(int $id): GroupTypeSettingResponse
    {
        return GroupTypeSettingResponse::fromArray($this->getById('/group-types', $id));
    }

    public function groupTypes(?ListSettingsRequest $request = null): GroupTypeSettingsResponse
    {
        return GroupTypeSettingsResponse::fromArray($this->get('/group-types', $request?->toQuery() ?? []));
    }

    public function language(int $id): LanguageResponse
    {
        return LanguageResponse::fromArray($this->getById('/languages', $id));
    }

    public function languages(?ListSettingsRequest $request = null): LanguagesResponse
    {
        return LanguagesResponse::fromArray($this->get('/languages', $request?->toQuery() ?? []));
    }

    public function lessonDetail(int $id): LessonDetailResponse
    {
        return LessonDetailResponse::fromArray($this->getById('/lesson-details', $id));
    }

    public function lessonDetails(?ListSettingsRequest $request = null): LessonDetailsResponse
    {
        return LessonDetailsResponse::fromArray($this->get('/lesson-details', $request?->toQuery() ?? []));
    }

    public function lessonStatus(int $id): LessonStatusResponse
    {
        return LessonStatusResponse::fromArray($this->getById('/lesson-statuses', $id));
    }

    public function lessonStatuses(?ListSettingsRequest $request = null): LessonStatusesResponse
    {
        return LessonStatusesResponse::fromArray($this->get('/lesson-statuses', $request?->toQuery() ?? []));
    }

    public function level(int $id): LevelResponse
    {
        return LevelResponse::fromArray($this->getById('/levels', $id));
    }

    public function levels(?ListSettingsRequest $request = null): LevelsResponse
    {
        return LevelsResponse::fromArray($this->get('/levels', $request?->toQuery() ?? []));
    }

    public function program(int $id): ProgramResponse
    {
        return ProgramResponse::fromArray($this->getById('/programs/collections', $id));
    }

    public function programItem(int $programId, int $itemId): ProgramItemResponse
    {
        return ProgramItemResponse::fromArray($this->transport->send('GET', $this->programItemPath(
            $programId,
            $itemId,
        )));
    }

    public function programItems(int $programId, ?ListProgramItemsRequest $request = null): ProgramItemsResponse
    {
        return ProgramItemsResponse::fromArray($this->transport->send(
            'GET',
            $this->programItemsPath($programId),
            $request?->toQuery() ?? [],
        ));
    }

    public function programs(?ListProgramsRequest $request = null): ProgramsResponse
    {
        return ProgramsResponse::fromArray($this->get('/programs/collections', $request?->toQuery() ?? []));
    }

    public function setProgramItemOrders(int $programId, SetProgramItemOrdersRequest $request): EmptyResponse
    {
        return EmptyResponse::fromArray($this->transport->send(
            'POST',
            $this->programItemsPath($programId) . '/set-orders',
            body: $request->toArray(),
        ));
    }

    public function setting(int $groupId, int $settingId): GroupSettingResponse
    {
        return GroupSettingResponse::fromArray($this->transport->send('GET', $this->settingPath($groupId, $settingId)));
    }

    public function settings(int $groupId, ?ListSettingsRequest $request = null): GroupSettingsResponse
    {
        return GroupSettingsResponse::fromArray($this->transport->send(
            'GET',
            $this->settingsPath($groupId),
            $request?->toQuery() ?? [],
        ));
    }

    public function subject(int $id): SubjectResponse
    {
        return SubjectResponse::fromArray($this->getById('/subjects', $id));
    }

    public function subjects(?ListSettingsRequest $request = null): SubjectsResponse
    {
        return SubjectsResponse::fromArray($this->get('/subjects', $request?->toQuery() ?? []));
    }

    public function updateProgram(int $id, UpdateProgramRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send(
            'PATCH',
            $this->pathById('/programs/collections', $id),
            body: $request->toArray(),
        ));
    }

    public function updateProgramItem(int $programId, int $itemId, UpsertProgramItemRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send(
            'PATCH',
            $this->programItemPath($programId, $itemId),
            body: $request->toArray(),
        ));
    }

    public function updateSetting(int $groupId, int $settingId, UpdateGroupSettingRequest $request): IdResponse
    {
        return IdResponse::fromArray($this->transport->send(
            'PATCH',
            $this->settingPath($groupId, $settingId),
            body: $request->toArray(),
        ));
    }

    /**
     * @param  array<string, int|string>  $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query): array
    {
        return $this->transport->send('GET', self::ROOT . $path, $query);
    }

    /** @return array<string, mixed> */
    private function getById(string $path, int $id): array
    {
        return $this->transport->send('GET', $this->pathById($path, $id));
    }

    private function gradeItemsPath(int $collectionId): string
    {
        return $this->pathById('/grades/collections', $collectionId) . '/items';
    }

    private function pathById(string $path, int $id): string
    {
        return ResourcePath::id(ltrim(self::ROOT . $path, '/'), $id);
    }

    private function programItemPath(int $programId, int $itemId): string
    {
        return ResourcePath::id(ltrim($this->programItemsPath($programId), '/'), $itemId);
    }

    private function programItemsPath(int $programId): string
    {
        return $this->pathById('/programs/collections', $programId) . '/items';
    }

    private function settingPath(int $groupId, int $settingId): string
    {
        return ResourcePath::id(ltrim($this->settingsPath($groupId), '/'), $settingId);
    }

    private function settingsPath(int $groupId): string
    {
        return ResourcePath::id('groups', $groupId) . '/settings';
    }
}
