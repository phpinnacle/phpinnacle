<?php

namespace PHPinnacle\Langcat\Tests\Unit;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPinnacle\Langcat\Client;
use PHPinnacle\Langcat\Enum\AssignType;
use PHPinnacle\Langcat\Enum\BillingCalculationBase;
use PHPinnacle\Langcat\Enum\BillingModel;
use PHPinnacle\Langcat\Enum\DocumentTemplateType;
use PHPinnacle\Langcat\Enum\EventContext;
use PHPinnacle\Langcat\Enum\EventType;
use PHPinnacle\Langcat\Enum\GradingType;
use PHPinnacle\Langcat\Enum\GroupType;
use PHPinnacle\Langcat\Enum\InstallmentTagType;
use PHPinnacle\Langcat\Enum\OnlineLessonProvider;
use PHPinnacle\Langcat\Enum\StudentType;
use PHPinnacle\Langcat\Enum\UserRole;
use PHPinnacle\Langcat\Enum\WebhookEvent;
use PHPinnacle\Langcat\Enum\Weekday;
use PHPinnacle\Langcat\Exception\ApiException;
use PHPinnacle\Langcat\Request\Authorization\AuthenticateRequest;
use PHPinnacle\Langcat\Request\Documents\ListDocumentTemplatesRequest;
use PHPinnacle\Langcat\Request\Events\ListEventsRequest;
use PHPinnacle\Langcat\Request\Finances\CreateInstallmentCollectionRequest;
use PHPinnacle\Langcat\Request\Finances\CreateInstallmentRequest;
use PHPinnacle\Langcat\Request\Finances\CreateInstallmentTagRequest;
use PHPinnacle\Langcat\Request\Finances\ListCollectionInstallmentsRequest;
use PHPinnacle\Langcat\Request\Finances\ListInstallmentCollectionsRequest;
use PHPinnacle\Langcat\Request\Finances\ListInstallmentTagsRequest;
use PHPinnacle\Langcat\Request\Finances\UpdateInstallmentCollectionRequest;
use PHPinnacle\Langcat\Request\Finances\UpdateInstallmentRequest;
use PHPinnacle\Langcat\Request\Finances\UpdateInstallmentTagRequest;
use PHPinnacle\Langcat\Request\Finances\UpdateInstallmentTagsRequest;
use PHPinnacle\Langcat\Request\Finances\WriteStudentInstallmentRequest;
use PHPinnacle\Langcat\Request\Groups\AssignStudentToGroupRequest;
use PHPinnacle\Langcat\Request\Groups\ChangeStudentAssignTypeRequest;
use PHPinnacle\Langcat\Request\Groups\CreateGroupRequest;
use PHPinnacle\Langcat\Request\Groups\CreateLessonRequest;
use PHPinnacle\Langcat\Request\Groups\LessonTeacherRequest;
use PHPinnacle\Langcat\Request\Groups\ListGradesRequest;
use PHPinnacle\Langcat\Request\Groups\ListGroupsRequest;
use PHPinnacle\Langcat\Request\Groups\ListGroupStudentsRequest;
use PHPinnacle\Langcat\Request\Groups\ListGroupTeachersRequest;
use PHPinnacle\Langcat\Request\Groups\ListLessonAttendanceRequest;
use PHPinnacle\Langcat\Request\Groups\ListLessonsRequest;
use PHPinnacle\Langcat\Request\Groups\SearchGroupsRequest;
use PHPinnacle\Langcat\Request\Groups\UpdateGroupRequest;
use PHPinnacle\Langcat\Request\Groups\UpdateLessonRequest;
use PHPinnacle\Langcat\Request\GroupSettings\CreateGroupSettingRequest;
use PHPinnacle\Langcat\Request\GroupSettings\CreateProgramRequest;
use PHPinnacle\Langcat\Request\GroupSettings\SetProgramItemOrdersRequest;
use PHPinnacle\Langcat\Request\GroupSettings\UpdateGroupSettingRequest;
use PHPinnacle\Langcat\Request\GroupSettings\UpdateProgramRequest;
use PHPinnacle\Langcat\Request\GroupSettings\UpsertProgramItemRequest;
use PHPinnacle\Langcat\Request\Schools\ListSchoolsRequest;
use PHPinnacle\Langcat\Request\Shared\ListConsentsRequest;
use PHPinnacle\Langcat\Request\Shared\ListDirectoryRequest;
use PHPinnacle\Langcat\Request\Shared\UpdateDetailsRequest;
use PHPinnacle\Langcat\Request\Students\AssignStudentToSchoolRequest;
use PHPinnacle\Langcat\Request\Students\CreateStudentRequest;
use PHPinnacle\Langcat\Request\Students\ListStudentsRequest;
use PHPinnacle\Langcat\Request\Students\SearchStudentsRequest;
use PHPinnacle\Langcat\Request\Students\UpdateParentRequest;
use PHPinnacle\Langcat\Request\Students\UpdateStudentRequest;
use PHPinnacle\Langcat\Request\Users\SearchUsersRequest;
use PHPinnacle\Langcat\Request\Webhooks\CreateWebhookRequest;
use PHPinnacle\Langcat\Request\Webhooks\ListWebhooksRequest;
use PHPinnacle\Langcat\Response\Events\EventStudentInGroupDetailsResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ClientTest extends TestCase
{
    public function test_it_accepts_a_no_content_response_when_assigning_a_school(): void
    {
        $http = new RecordingHttpClient(new Response(204));
        $client = $this->client($http)->withAccessToken('access-token');

        $client->students()->assignToSchool(
            42,
            AssignStudentToSchoolRequest::make()->schoolId(3),
        );

        self::assertNotNull($http->lastRequest);
        self::assertSame('/api/v4/students/42/schools', $http->lastRequest->getUri()->getPath());
        self::assertSame('{"schoolId":3}', (string) $http->lastRequest->getBody());
    }

    public function test_it_authenticates_with_a_fluent_request(): void
    {
        $http = new RecordingHttpClient(
            new Response(200, [], json_encode([
                'accessToken' => 'access-token',
                'refreshToken' => 'refresh-token',
                'tokenType' => 'Bearer',
                'expiresIn' => 3600,
                'scope' => 'apiv4',
            ], JSON_THROW_ON_ERROR)),
        );
        $client = $this->client($http);

        $response = $client
            ->authorization()
            ->authenticate(
                AuthenticateRequest::make()
                    ->clientId('client-id')
                    ->clientSecret('client-secret'),
            );

        self::assertSame('access-token', $response->accessToken);
        self::assertNotNull($http->lastRequest);
        self::assertSame('clientId=client-id&clientSecret=client-secret', (string) $http->lastRequest->getBody());
        self::assertSame('application/x-www-form-urlencoded', $http->lastRequest->getHeaderLine('Content-Type'));
    }

    public function test_it_creates_a_student_with_json_and_returns_an_id(): void
    {
        $http = new RecordingHttpClient(new Response(200, [], '{"id":387}'));
        $client = $this->client($http)->withAccessToken('access-token');

        $response = $client
            ->students()
            ->create(
                CreateStudentRequest::make()
                    ->firstName('Adam')
                    ->lastName('Smith')
                    ->schoolId(1)
                    ->hasParentAccounts(false)
                    ->type(StudentType::Regular),
            );

        self::assertSame(387, $response->id);
        self::assertNotNull($http->lastRequest);
        self::assertSame('POST', $http->lastRequest->getMethod());
        self::assertSame(
            '{"firstName":"Adam","lastName":"Smith","schoolId":1,"hasParentAccounts":false,"type":"regular"}',
            (string) $http->lastRequest->getBody(),
        );
    }

    public function test_it_preserves_api_error_details(): void
    {
        $client = $this->client(new RecordingHttpClient(
            new Response(403, [], '{"message":"Forbidden","docs":"https://example.test/docs"}'),
        ))->withAccessToken('access-token');

        try {
            $client->authorization()->me();
            self::fail('Expected an API exception.');
        } catch (ApiException $exception) {
            self::assertSame(403, $exception->statusCode);
            self::assertNotNull($exception->response);
            self::assertSame('Forbidden', $exception->response->message);
            self::assertSame('https://example.test/docs', $exception->response->docs);
        }
    }

    public function test_it_returns_typed_students_and_sends_filters(): void
    {
        $http = new RecordingHttpClient(
            new Response(200, [], json_encode([
                'data' => [[
                    'id' => 42,
                    'name' => 'Ada',
                    'lastName' => 'Lovelace',
                    'type' => 'lead',
                    'schoolIds' => [1, 2],
                    'hasParentAccounts' => false,
                    'isArchived' => false,
                    'archivedAt' => null,
                    'createdAt' => '2026-08-30 10:00:00',
                ]],
                'meta' => ['count' => 1, 'total' => 1],
            ], JSON_THROW_ON_ERROR)),
        );
        $client = $this->client($http)->withAccessToken('access-token');

        $response = $client->students()->all(
            ListStudentsRequest::make()
                ->page(2)
                ->perPage(10)
                ->sortBy('-id')
                ->archived(false)
                ->type(StudentType::Lead)
                ->schoolId(3),
        );

        self::assertSame(42, $response->data[0]->id);
        self::assertSame(1, $response->meta->total);
        self::assertNotNull($http->lastRequest);
        self::assertSame(
            'page=2&perPage=10&sortBy=-id&isArchived=0&type=lead&schoolId=3',
            $http->lastRequest->getUri()->getQuery(),
        );
        self::assertSame('Bearer access-token', $http->lastRequest->getHeaderLine('Authorization'));
    }

    public function test_it_searches_students_with_filters_and_hydrates_expansions(): void
    {
        $http = new RecordingHttpClient(new Response(200, [], json_encode([
            'data' => [[
                'id' => 42,
                'student.basic' => [
                    'name' => 'Ada',
                    'lastName' => 'Lovelace',
                    'type' => 'lead',
                    'isArchived' => false,
                    'archivedAt' => null,
                    'createdAt' => '2026-08-30 10:00:00',
                ],
                'student.details' => ['email' => 'ada@example.test'],
                'parents' => [[
                    'id' => 7,
                    'parents.basics' => [
                        'name' => 'Ann',
                        'lastName' => 'Lovelace',
                        'isArchived' => false,
                        'archivedAt' => null,
                        'createdAt' => '2026-08-30 09:00:00',
                    ],
                ]],
            ]],
            'meta' => ['count' => 1, 'total' => 1],
        ], JSON_THROW_ON_ERROR)));

        $response = $this
            ->client($http)
            ->withAccessToken('access-token')
            ->students()
            ->search(
                SearchStudentsRequest::make()
                    ->page(2)
                    ->perPage(10)
                    ->sortBy('-id')
                    ->archived(false)
                    ->type(StudentType::Lead)
                    ->schoolId(3)
                    ->expand('student.basic', 'parents.basic')
                    ->createdAt('2026-01-15')
                    ->createdAfter('2026-01-01')
                    ->createdOnOrAfter('2026-01-02')
                    ->createdBefore('2026-02-01')
                    ->createdOnOrBefore('2026-01-31'),
            );

        self::assertSame('Ada', $response->data[0]->basic?->name);
        self::assertSame('ada@example.test', $response->data[0]->details?->personal?->email);
        self::assertSame('Ann', $response->data[0]->parents[0]->basic?->name);
        self::assertNotNull($http->lastRequest);
        self::assertSame(
            'page=2&perPage=10&sortBy=-id&isArchived=0&type=lead&schoolId=3&createdAt=2026-01-15&createdAt_gt=2026-01-01&createdAt_ge=2026-01-02&createdAt_lt=2026-02-01&createdAt_le=2026-01-31&expand%5B0%5D=student.basic&expand%5B1%5D=parents.basic',
            $http->lastRequest->getUri()->getQuery(),
        );
    }

    public function test_it_supports_all_core_group_endpoints(): void
    {
        $group = [
            'id' => 20,
            'name' => 'English A1',
            'schoolId' => 3,
            'startsOn' => '2026-09-01',
            'endsOn' => null,
            'numberOfLessons' => 30,
            'lessonDuration' => 45,
            'type' => 'group',
            'billing' => ['model' => 'whole'],
            'minStudents' => null,
            'maxStudents' => 12,
            'gradingType' => 'percentage',
            'languageId' => 1,
            'levelId' => 2,
            'programCollectionId' => null,
            'documentTemplateId' => null,
            'installmentCollectionId' => null,
            'companyId' => null,
            'companyPrice' => null,
            'isPlanned' => true,
            'createdAt' => '2026-08-30 10:00:00',
        ];
        $groupSearch = [
            'id' => 20,
            'group.basic' => ['id' => 20, 'name' => 'English A1'],
            'teachers' => [['id' => 5, 'teachers.basic' => ['id' => 5, 'name' => 'Grace Hopper']]],
        ];
        $lesson = [
            'id' => 21,
            'startAt' => '2026-09-01 09:00:00',
            'endAt' => '2026-09-01 09:45:00',
            'length' => 45,
            'breakLength' => 5,
            'teachers' => [5],
            'statusId' => 2,
            'classroomId' => 4,
            'attendance' => ['isChecked' => true, 'checkedAt' => '2026-09-01 10:00:00'],
            'homework' => ['isChecked' => false, 'checkedAt' => null],
            'isVisible' => true,
            'onlineLesson' => ['provider' => 'MeetingLink', 'url' => 'https://example.test/lesson'],
            'createdAt' => '2026-08-30 10:00:00',
        ];
        $student = [
            'id' => 42,
            'assignType' => 'assigned',
            'enrolledAt' => '2026-09-01 08:00:00',
            'dischargedAt' => null,
            'agreement' => ['dueDate' => null, 'signedDate' => null, 'billingModel' => 'whole'],
        ];
        $manualGrade = [
            'id' => 30,
            'name' => 'Quiz',
            'position' => 1,
            'color' => '49a8e1',
            'weight' => 2,
            'grade' => ['value' => 3.75, 'formatted' => '3+', 'teacherId' => 5, 'gradedAt' => '2026-09-01 10:00:00'],
            'createdAt' => '2026-09-01 10:00:00',
        ];
        $predefinedGrade = [
            'id' => 31,
            'name' => 'Grammar',
            'weight' => 2,
            'color' => '49a8e1',
            'maxValue' => 5,
            'isShared' => false,
            'createdAt' => '2026-09-01 10:00:00',
            'collectionId' => 7,
            'grade' => ['value' => 'np', 'formatted' => 'np', 'teacherId' => 5, 'gradedAt' => null],
        ];
        $list = static fn (array $item) => json_encode([
            'data' => [$item],
            'meta' => ['count' => 1, 'total' => 1],
        ], JSON_THROW_ON_ERROR);
        $json = static fn (array $value) => json_encode($value, JSON_THROW_ON_ERROR);
        $http = new RecordingHttpClient(
            new Response(200, [], $list($group)),
            new Response(200, [], $list($groupSearch)),
            new Response(200, [], $json($group)),
            new Response(200, [], '[]'),
            new Response(200, [], '{"id":20}'),
            new Response(200, [], $list($lesson)),
            new Response(200, [], $json($lesson)),
            new Response(200, [], '{"id":21}'),
            new Response(200, [], '{"id":21}'),
            new Response(200, [], '[{"studentId":42,"attendanceStatusId":2}]'),
            new Response(200, [], $list($student)),
            new Response(200, [], $json($student)),
            new Response(200, [], '[]'),
            new Response(200, [], '[]'),
            new Response(200, [], $list($manualGrade)),
            new Response(200, [], $list($predefinedGrade)),
            new Response(200, [], $list(['id' => 5])),
            new Response(200, [], '{"id":5}'),
        );
        $groups = $this->client($http)->withAccessToken('access-token')->groups();

        self::assertSame(GroupType::Group, $groups->all(ListGroupsRequest::make()->schoolId(3))->data[0]->type);
        self::assertSame(
            'Grace Hopper',
            $groups->search(SearchGroupsRequest::make()->expand(
                'group.basic',
                'teachers.basic',
            ))->data[0]->teachers[0]->basic?->name,
        );
        self::assertSame(20, $groups->get(20)->id);
        $groups->create(
            CreateGroupRequest::make()
                ->name('English A1')
                ->schoolId(3)
                ->startsOn('2026-09-01')
                ->numberOfLessons(30)
                ->lessonDuration(45)
                ->type(GroupType::Group)
                ->billingModel(BillingModel::Hour)
                ->billingCalculationBase(BillingCalculationBase::Lesson)
                ->billingUnitPrice('120.00')
                ->gradingType(GradingType::Percentage),
        );
        self::assertSame(20, $groups->update(20, UpdateGroupRequest::make()->planned(false))->id);
        self::assertSame(
            21,
            $groups->lessons(20, ListLessonsRequest::make()->startsOnOrAfter('2026-09-01'))->data[0]->id,
        );
        self::assertSame(OnlineLessonProvider::MeetingLink, $groups->lesson(20, 21)->onlineLesson?->provider);
        self::assertSame(
            21,
            $groups->createLesson(
                20,
                CreateLessonRequest::make()
                    ->settingId(38)
                    ->date('2026-09-01')
                    ->startTime('09:00')
                    ->lessonLength(45)
                    ->teachers(LessonTeacherRequest::make()->id(5)->salary(100)),
            )->id,
        );
        self::assertSame(21, $groups->updateLesson(20, 21, UpdateLessonRequest::make()->breakLength(10))->id);
        self::assertSame(
            42,
            $groups->lessonAttendance(
                20,
                21,
                ListLessonAttendanceRequest::make()->sortBy('+studentId'),
            )->data[0]->studentId,
        );
        self::assertSame(
            AssignType::Assigned,
            $groups->students(
                20,
                ListGroupStudentsRequest::make()->assignType(AssignType::Assigned),
            )->data[0]->assignType,
        );
        self::assertSame(42, $groups->student(20, 42)->id);
        $groups->assignStudent(
            20,
            AssignStudentToGroupRequest::make()->studentId(42)->assignType(AssignType::Assigned),
        );
        $groups->changeStudentAssignType(
            20,
            42,
            ChangeStudentAssignTypeRequest::make()->assignType(AssignType::Waiting),
        );
        self::assertSame(
            3.75,
            $groups->manualGrades(20, 42, ListGradesRequest::make()->sortBy('+position'))->data[0]->grade?->value,
        );
        self::assertSame('np', $groups->predefinedGrades(20, 42)->data[0]->grade?->value);
        self::assertSame(5, $groups->teachers(20, ListGroupTeachersRequest::make()->sortBy('+teacherId'))->data[0]->id);
        self::assertSame(5, $groups->teacher(20, 5)->id);

        self::assertSame('/api/v4/groups/20/lessons/21/attendance', $http->requests[9]->getUri()->getPath());
        self::assertSame('{"assignType":"waiting"}', (string) $http->requests[13]->getBody());
        self::assertSame('/api/v4/groups/20/teachers/5', $http->requests[17]->getUri()->getPath());
    }

    public function test_it_supports_all_finance_endpoints(): void
    {
        $collection = [
            'id' => 1,
            'schoolId' => 3,
            'name' => 'Semester',
            'isShared' => false,
            'createdAt' => '2026-08-30 10:00:00',
        ];
        $installment = [
            'id' => 2,
            'value' => 250,
            'paidValue' => 50,
            'dueDate' => '2026-09-30',
            'tagIds' => [3],
            'createdAt' => '2026-08-30 10:00:00',
            'dateTo' => '2026-09-30',
        ];
        $tag = [
            'id' => 3,
            'name' => 'vip',
            'type' => 'installmentCollectionItem',
            'visibleToStudent' => true,
            'createdAt' => '2026-08-30 10:00:00',
        ];
        $list = static fn (array $item) => json_encode([
            'data' => [$item],
            'meta' => ['count' => 1, 'total' => 1],
        ], JSON_THROW_ON_ERROR);
        $json = static fn (array $value) => json_encode($value, JSON_THROW_ON_ERROR);
        $id = new Response(200, [], '{"id":2}');
        $http = new RecordingHttpClient(
            new Response(200, [], $list($collection)),
            new Response(200, [], $json($collection)),
            $id,
            $id,
            $id,
            new Response(200, [], $list($installment)),
            new Response(200, [], $json($installment)),
            $id,
            $id,
            $id,
            $id,
            new Response(200, [], $list($tag)),
            new Response(200, [], $json($tag)),
            $id,
            $id,
            new Response(200, [], $list($installment)),
            new Response(200, [], $json($installment)),
            $id,
            $id,
            $id,
        );
        $finances = $this->client($http)->withAccessToken('access-token')->finances();

        self::assertSame(
            1,
            $finances->installmentCollections(ListInstallmentCollectionsRequest::make()->schoolId(3))->data[0]->id,
        );
        self::assertSame(1, $finances->installmentCollection(1)->id);
        $finances->createInstallmentCollection(
            CreateInstallmentCollectionRequest::make()->schoolId(3)->name('Semester'),
        );
        $finances->updateInstallmentCollection(1, UpdateInstallmentCollectionRequest::make()->name('Semester 2'));
        $finances->deleteInstallmentCollection(1);
        self::assertSame(
            2,
            $finances->collectionInstallments(
                1,
                ListCollectionInstallmentsRequest::make()->sortBy('+dateTo'),
            )->data[0]->id,
        );
        self::assertSame(2, $finances->collectionInstallment(1, 2)->id);
        $finances->createCollectionInstallment(1, CreateInstallmentRequest::make()->value(250)->dateTo('2026-09-30'));
        $finances->updateCollectionInstallment(1, 2, UpdateInstallmentRequest::make()->value(200));
        $finances->deleteCollectionInstallment(1, 2);
        $finances->updateCollectionInstallmentTags(1, 2, UpdateInstallmentTagsRequest::make()->tag(3));
        self::assertSame(
            3,
            $finances->installmentTags(ListInstallmentTagsRequest::make()->type(InstallmentTagType::InstallmentCollectionItem))->data[0]->id,
        );
        self::assertSame(3, $finances->installmentTag(3)->id);
        $finances->createInstallmentTag(
            CreateInstallmentTagRequest::make()->name('vip')->type(InstallmentTagType::InstallmentCollectionItem),
        );
        $finances->updateInstallmentTag(3, UpdateInstallmentTagRequest::make()->visibleToStudent(false));
        self::assertSame(2, $finances->studentInstallments(20, 42)->data[0]->id);
        self::assertSame(2, $finances->studentInstallment(20, 42, 2)->id);
        $studentInstallment = WriteStudentInstallmentRequest::make()->value(150)->dueDate('2026-09-30');
        $finances->createStudentInstallment(20, 42, $studentInstallment);
        $finances->updateStudentInstallment(20, 42, 2, $studentInstallment);
        $finances->updateStudentInstallmentTags(20, 42, 2, UpdateInstallmentTagsRequest::make()->tag(3));

        self::assertSame(
            '/api/v4/finances/settings/installments/collections/1/items/2/tags',
            $http->requests[10]->getUri()->getPath(),
        );
        self::assertSame('/api/v4/groups/20/students/42/installments/2/tags', $http->requests[19]->getUri()->getPath());
        self::assertCount(20, $http->requests);
    }

    public function test_it_supports_all_group_setting_endpoints(): void
    {
        $createdAt = '2026-08-30 10:00:00';
        $course = [
            'id' => 1,
            'name' => 'English A1',
            'schoolId' => 3,
            'languageId' => 4,
            'levelId' => 5,
            'ageGroupId' => 6,
            'courseBookId' => 7,
            'programCollectionId' => null,
            'isShared' => false,
            'isArchived' => false,
            'createdAt' => $createdAt,
        ];
        $level = ['id' => 5, 'name' => 'A1', 'schoolId' => 3, 'isShared' => false, 'createdAt' => $createdAt];
        $lessonStatus = [
            'id' => 8,
            'schoolId' => 3,
            'isShared' => false,
            'name' => 'Scheduled',
            'type' => 'scheduled',
            'studentPercent' => 100,
            'teacherPercent' => 100,
            'color' => '49a8e1',
            'createdAt' => $createdAt,
        ];
        $groupType = ['id' => 9, 'name' => 'Standard', 'createdAt' => $createdAt];
        $gradeCollection = [
            'id' => 10,
            'name' => 'Grades',
            'schoolId' => 3,
            'isShared' => false,
            'createdAt' => $createdAt,
        ];
        $gradeItem = [
            'id' => 11,
            'name' => 'Grammar',
            'weight' => 2,
            'color' => '49a8e1',
            'maxValue' => 5,
            'isShared' => false,
            'createdAt' => $createdAt,
            'collectionId' => 10,
            'grade' => null,
        ];
        $courseBook = [
            'id' => 7,
            'name' => 'Book',
            'description' => null,
            'languageId' => 4,
            'levelId' => 5,
            'schoolId' => 3,
            'isShared' => false,
            'isArchived' => false,
            'createdAt' => $createdAt,
        ];
        $attendanceStatus = [
            'id' => 12,
            'schoolId' => 3,
            'isShared' => false,
            'name' => 'Present',
            'shortName' => 'P',
            'type' => 'present',
            'studentPercent' => 100,
            'attendancePercent' => 100,
            'color' => '49a8e1',
            'createdAt' => $createdAt,
        ];
        $lessonDetail = [
            'id' => 13,
            'schoolId' => 3,
            'isShared' => false,
            'position' => 1,
            'name' => 'Warm-up',
            'color' => '49a8e1',
            'onlyForTeacher' => false,
            'createdAt' => $createdAt,
        ];
        $program = [
            'id' => 14,
            'name' => 'General English',
            'schoolId' => 3,
            'isShared' => false,
            'createdAt' => $createdAt,
        ];
        $programItem = [
            'id' => 15,
            'position' => 1,
            'elements' => [['lessonDetailsId' => 13, 'description' => 'Warm-up']],
            'createdAt' => $createdAt,
        ];
        $language = [
            'id' => 4,
            'name' => 'English',
            'color' => '49a8e1',
            'schoolId' => 3,
            'isShared' => false,
            'createdAt' => $createdAt,
        ];
        $subject = [
            'id' => 16,
            'name' => 'Grammar',
            'shortName' => 'GR',
            'color' => '49a8e1',
            'schoolId' => 3,
            'isShared' => false,
            'description' => 'Grammar',
            'languageId' => 4,
            'levelId' => 5,
            'createdAt' => $createdAt,
        ];
        $setting = [
            'id' => 17,
            'subjectId' => 16,
            'teachers' => [['id' => 5, 'salary' => 100]],
            'classroomId' => 2,
            'day' => 'monday',
            'startTime' => '09:00',
            'lessonLength' => 45,
            'breakLength' => 5,
            'isOnlineLessonEnabled' => false,
            'onlineLessonProvider' => null,
            'createdAt' => $createdAt,
        ];
        $ageGroup = ['id' => 18, 'name' => 'Adults', 'ageFrom' => 18, 'ageTo' => 99, 'schoolId' => 3];
        $list = static fn (array $item) => json_encode([
            'data' => [$item],
            'meta' => ['count' => 1, 'total' => 1],
        ], JSON_THROW_ON_ERROR);
        $json = static fn (array $value) => json_encode($value, JSON_THROW_ON_ERROR);
        $responses = [];
        foreach ([
            $course,
            $level,
            $lessonStatus,
            $groupType,
            $gradeCollection,
            $gradeItem,
            $courseBook,
            $attendanceStatus,
            $lessonDetail,
        ] as $item) {
            $responses[] = new Response(200, [], $list($item));
            $responses[] = new Response(200, [], $json($item));
        }
        $responses = [
            ...$responses,
            new Response(200, [], $list($program)),
            new Response(200, [], $json($program)),
            new Response(200, [], '{"id":14}'),
            new Response(200, [], '{"id":14}'),
            new Response(200, [], '[]'),
            new Response(200, [], $list($programItem)),
            new Response(200, [], $json($programItem)),
            new Response(200, [], '{"id":15}'),
            new Response(200, [], '{"id":15}'),
            new Response(200, [], '[]'),
            new Response(200, [], '[]'),
            new Response(200, [], $list($language)),
            new Response(200, [], $json($language)),
            new Response(200, [], $list($subject)),
            new Response(200, [], $json($subject)),
            new Response(200, [], $list($setting)),
            new Response(200, [], $json($setting)),
            new Response(200, [], '{"id":17}'),
            new Response(200, [], '{"id":17}'),
            new Response(200, [], $list($ageGroup)),
            new Response(200, [], $json($ageGroup)),
        ];
        $http = new RecordingHttpClient(...$responses);
        $settings = $this->client($http)->withAccessToken('access-token')->groupSettings();

        self::assertSame(1, $settings->courses()->data[0]->id);
        self::assertSame(1, $settings->course(1)->id);
        self::assertSame(5, $settings->levels()->data[0]->id);
        self::assertSame(5, $settings->level(5)->id);
        self::assertSame(8, $settings->lessonStatuses()->data[0]->id);
        self::assertSame(8, $settings->lessonStatus(8)->id);
        self::assertSame(9, $settings->groupTypes()->data[0]->id);
        self::assertSame(9, $settings->groupType(9)->id);
        self::assertSame(10, $settings->gradeCollections()->data[0]->id);
        self::assertSame(10, $settings->gradeCollection(10)->id);
        self::assertSame(11, $settings->gradeCollectionItems(10)->data[0]->id);
        self::assertSame(11, $settings->gradeCollectionItem(10, 11)->id);
        self::assertSame(7, $settings->courseBooks()->data[0]->id);
        self::assertSame(7, $settings->courseBook(7)->id);
        self::assertSame(12, $settings->attendanceStatuses()->data[0]->id);
        self::assertSame(12, $settings->attendanceStatus(12)->id);
        self::assertSame(13, $settings->lessonDetails()->data[0]->id);
        self::assertSame(13, $settings->lessonDetail(13)->id);
        self::assertSame(14, $settings->programs()->data[0]->id);
        self::assertSame(14, $settings->program(14)->id);
        self::assertSame(
            14,
            $settings->createProgram(CreateProgramRequest::make()->schoolId(3)->name('General English'))->id,
        );
        self::assertSame(14, $settings->updateProgram(14, UpdateProgramRequest::make()->name('English Plus'))->id);
        $settings->deleteProgram(14);
        self::assertSame(15, $settings->programItems(14)->data[0]->id);
        self::assertSame(15, $settings->programItem(14, 15)->id);
        self::assertSame(
            15,
            $settings->createProgramItem(14, UpsertProgramItemRequest::make()->element(13, 'Warm-up'))->id,
        );
        self::assertSame(
            15,
            $settings->updateProgramItem(
                14,
                15,
                UpsertProgramItemRequest::make()->position(2)->element(13, 'Review'),
            )->id,
        );
        $settings->deleteProgramItem(14, 15);
        $settings->setProgramItemOrders(14, SetProgramItemOrdersRequest::make()->item(15, 1));
        self::assertSame(4, $settings->languages()->data[0]->id);
        self::assertSame(4, $settings->language(4)->id);
        self::assertSame(16, $settings->subjects()->data[0]->id);
        self::assertSame(16, $settings->subject(16)->id);
        self::assertSame(17, $settings->settings(20)->data[0]->id);
        self::assertSame(17, $settings->setting(20, 17)->id);
        self::assertSame(
            17,
            $settings->createSetting(
                20,
                CreateGroupSettingRequest::make()
                    ->subjectId(16)
                    ->day(Weekday::Monday)
                    ->startTime('09:00')
                    ->lessonLength(45),
            )->id,
        );
        self::assertSame(
            17,
            $settings->updateSetting(20, 17, UpdateGroupSettingRequest::make()->onlineLesson(false))->id,
        );
        self::assertSame(18, $settings->ageGroups()->data[0]->id);
        self::assertSame(18, $settings->ageGroup(18)->id);

        self::assertSame(
            '/api/v4/groups/settings/programs/collections/14/items/set-orders',
            $http->requests[28]->getUri()->getPath(),
        );
        self::assertSame('/api/v4/groups/20/settings/17', $http->requests[36]->getUri()->getPath());
        self::assertCount(39, $http->requests);
    }

    public function test_it_supports_directory_and_profile_endpoints(): void
    {
        $administrator = [
            'id' => 1,
            'name' => 'Ada',
            'lastName' => 'Admin',
            'role' => 'administrator',
            'isArchived' => false,
            'archivedAt' => null,
            'createdAt' => '2026-08-30 08:00:00',
        ];
        $classroom = [
            'id' => 2,
            'name' => 'Room 2',
            'schoolIds' => [3],
            'maxStudentsNumber' => 20,
            'address' => 'Main Street',
            'description' => 'Projector',
            'color' => 'DDE5F6',
            'isExternal' => false,
            'isArchived' => false,
            'archivedAt' => null,
            'createdAt' => '2026-08-30 08:00:00',
        ];
        $company = [
            'id' => 3,
            'name' => 'Acme Ltd',
            'shortName' => 'Acme',
            'billingModel' => 'hour-in-advance',
            'isArchived' => false,
            'archivedAt' => null,
            'createdAt' => '2026-08-30 08:00:00',
        ];
        $school = ['id' => 4, 'name' => 'Main School', 'shortName' => 'Main', 'createdAt' => '2026-08-30'];
        $teacher = [
            'id' => 5,
            'name' => 'Grace',
            'lastName' => 'Hopper',
            'isArchived' => false,
            'archivedAt' => null,
            'createdAt' => '2026-08-30 08:00:00',
        ];
        $details = ['personal' => ['email' => 'profile@example.test'], 'address' => null, 'billing' => null];
        $consents = [
            'data' => [['id' => 6, 'consentId' => 7, 'value' => true, 'createdAt' => '2026-08-30 08:00:00']],
            'meta' => ['count' => 1, 'total' => 1],
        ];
        $users = [
            'data' => [['id' => 8, 'role' => 'teacher', 'name' => 'Grace', 'lastName' => 'Hopper']],
            'meta' => ['count' => 1, 'total' => 1],
        ];
        $list = static fn (array $item) => json_encode([
            'data' => [$item],
            'meta' => ['count' => 1, 'total' => 1],
        ], JSON_THROW_ON_ERROR);
        $json = static fn (array $value) => json_encode($value, JSON_THROW_ON_ERROR);
        $http = new RecordingHttpClient(
            new Response(200, [], $list($administrator)),
            new Response(200, [], $json($administrator)),
            new Response(200, [], $json($details)),
            new Response(200, [], $list($classroom)),
            new Response(200, [], $json($classroom)),
            new Response(200, [], $list($company)),
            new Response(200, [], $json($company)),
            new Response(200, [], $json($details)),
            new Response(200, [], '{"login":"acme"}'),
            new Response(200, [], $list($school)),
            new Response(200, [], $json($school)),
            new Response(200, [], $list($teacher)),
            new Response(200, [], $json($teacher)),
            new Response(200, [], $json($details)),
            new Response(204),
            new Response(200, [], $json($consents)),
            new Response(200, [], $json($users)),
        );
        $client = $this->client($http)->withAccessToken('access-token');
        $directory = ListDirectoryRequest::make()
            ->page(2)
            ->perPage(10)
            ->sortBy('-id')
            ->archived(false)
            ->schoolId(3);

        self::assertSame(UserRole::Administrator, $client->administrators()->all($directory)->data[0]->role);
        self::assertSame(1, $client->administrators()->get(1)->id);
        self::assertSame('profile@example.test', $client->administrators()->details(1)->personal?->email);
        self::assertSame(20, $client->classrooms()->all($directory)->data[0]->maxStudentsNumber);
        self::assertSame('Room 2', $client->classrooms()->get(2)->name);
        self::assertSame(BillingModel::HourInAdvance, $client->companies()->all($directory)->data[0]->billingModel);
        self::assertSame('Acme', $client->companies()->get(3)->shortName);
        self::assertSame('profile@example.test', $client->companies()->details(3)->personal?->email);
        self::assertSame('acme', $client->companies()->access(3)->login);
        self::assertSame(
            'Main School',
            $client->schools()->all(ListSchoolsRequest::make()->perPage(400)->sortBy('+name'))->data[0]->name,
        );
        self::assertSame(4, $client->schools()->get(4)->id);
        self::assertSame('Grace', $client->teachers()->all($directory)->data[0]->name);
        self::assertSame(5, $client->teachers()->get(5)->id);
        self::assertSame('profile@example.test', $client->teachers()->details(5)->personal?->email);
        $client->teachers()->updateDetails(5, UpdateDetailsRequest::make()->phone('123'));
        self::assertSame(
            7,
            $client->teachers()->consents(5, ListConsentsRequest::make()->sortBy('+id'))->data[0]->consentId,
        );
        self::assertSame(
            UserRole::Teacher,
            $client
                ->users()
                ->search(
                    SearchUsersRequest::make()
                        ->page(1)
                        ->perPage(20)
                        ->sortBy('+id')
                        ->archived(false)
                        ->fullNameLike('Grace H')
                        ->email('grace@example.test')
                        ->role(UserRole::Teacher),
                )
                ->data[0]->role,
        );

        self::assertSame(
            'page=2&perPage=10&sortBy=-id&isArchived=0&schoolId=3',
            $http->requests[0]->getUri()->getQuery(),
        );
        self::assertSame('/api/v4/companies/3/access', $http->requests[8]->getUri()->getPath());
        self::assertSame('perPage=400&sortBy=%2Bname', $http->requests[9]->getUri()->getQuery());
        self::assertSame('{"phone":"123"}', (string) $http->requests[14]->getBody());
        self::assertSame(
            'page=1&perPage=20&sortBy=%2Bid&isArchived=0&fullName_like=Grace%20H&email=grace%40example.test&role=teacher',
            $http->requests[16]->getUri()->getQuery(),
        );
    }

    public function test_it_supports_events_documents_and_webhooks(): void
    {
        $event = [
            'id' => 9,
            'type' => 'update',
            'context' => 'studentInGroup',
            'details' => [
                'student' => ['id' => 42, 'fullName' => 'Ada Lovelace'],
                'group' => ['id' => 10, 'name' => 'English A1'],
            ],
            'createdBy' => ['id' => 5, 'role' => 'teacher', 'name' => 'Grace', 'lastName' => 'Hopper'],
            'ipAddress' => '192.0.2.1',
            'date' => '2026-08-30 10:00:00',
        ];
        $template = [
            'id' => 11,
            'type' => 'certificate',
            'title' => 'Certificate',
            'margin' => ['top' => 15, 'left' => 20, 'right' => 20, 'bottom' => 15],
            'description' => 'Course certificate',
            'content' => '<p>Certificate</p>',
            'eDocument' => true,
            'schoolId' => 3,
            'isShared' => false,
            'createdAt' => '2026-08-30 10:00:00',
        ];
        $webhook = [
            'id' => 12,
            'event' => 'student.created',
            'url' => 'https://example.test/hooks/langlion',
            'createdAt' => '2026-08-30 10:00:00',
        ];
        $list = static fn (array $item) => json_encode([
            'data' => [$item],
            'meta' => ['count' => 1, 'total' => 1],
        ], JSON_THROW_ON_ERROR);
        $json = static fn (array $value) => json_encode($value, JSON_THROW_ON_ERROR);
        $http = new RecordingHttpClient(
            new Response(200, [], $list($event)),
            new Response(200, [], $json($event)),
            new Response(200, [], $list($template)),
            new Response(200, [], $json($template)),
            new Response(200, [], $list($webhook)),
            new Response(200, [], $json($webhook)),
            new Response(200, [], '{"id":13}'),
            new Response(204),
        );
        $client = $this->client($http)->withAccessToken('access-token');

        $events = $client->events()->all(
            ListEventsRequest::make()
                ->page(2)
                ->perPage(25)
                ->sortBy('-date')
                ->dateOnOrAfter('2026-08-01')
                ->context(EventContext::StudentInGroup)
                ->type(EventType::Update)
                ->teacherId(5),
        );
        self::assertSame(EventType::Update, $events->data[0]->type);
        $details = $client->events()->get(9)->details;
        self::assertInstanceOf(EventStudentInGroupDetailsResponse::class, $details);
        self::assertSame('Ada Lovelace', $details->student->fullName);

        self::assertSame(
            DocumentTemplateType::Certificate,
            $client
                ->documents()
                ->templates(
                    ListDocumentTemplatesRequest::make()
                        ->sortBy('+id')
                        ->schoolId(3)
                        ->titleLike('Cert')
                        ->type(DocumentTemplateType::Certificate),
                )
                ->data[0]->type,
        );
        self::assertSame(15, $client->documents()->template(11)->margin->top);

        self::assertSame(
            WebhookEvent::StudentCreated,
            $client->webhooks()->all(ListWebhooksRequest::make()->page(1)->perPage(20)->sortBy('-id'))->data[0]->event,
        );
        self::assertSame('2026-08-30 10:00:00', $client->webhooks()->get(12)->createdAt);
        self::assertSame(
            13,
            $client
                ->webhooks()
                ->create(
                    CreateWebhookRequest::make()
                        ->event(WebhookEvent::DocumentSigned)
                        ->url('https://example.test/hooks/documents'),
                )
                ->id,
        );
        $client->webhooks()->delete(12);

        self::assertSame(
            'page=2&perPage=25&sortBy=-date&date_ge=2026-08-01&context=studentInGroup&type=update&teacherId=5',
            $http->requests[0]->getUri()->getQuery(),
        );
        self::assertSame(
            'sortBy=%2Bid&schoolId=3&title_like=Cert&type=certificate',
            $http->requests[2]->getUri()->getQuery(),
        );
        self::assertSame(
            '{"event":"document.signed","url":"https:\/\/example.test\/hooks\/documents"}',
            (string) $http->requests[6]->getBody(),
        );
        self::assertSame('DELETE', $http->requests[7]->getMethod());
    }

    public function test_it_supports_student_details_and_parent_endpoints(): void
    {
        $details = json_encode([
            'personal' => ['email' => 'ada@example.test'],
            'address' => ['city' => 'London'],
            'billing' => ['name' => 'Ada Lovelace'],
        ], JSON_THROW_ON_ERROR);
        $parent = json_encode([
            'id' => 7,
            'name' => 'Ann',
            'lastName' => 'Lovelace',
            'isArchived' => false,
            'archivedAt' => null,
            'createdAt' => '2026-08-30 09:00:00',
        ], JSON_THROW_ON_ERROR);
        $parents = json_encode([
            'data' => [json_decode($parent, true, flags: JSON_THROW_ON_ERROR)],
            'meta' => ['count' => 1, 'total' => 1],
        ], JSON_THROW_ON_ERROR);
        $consents = json_encode([
            'data' => [['id' => 4, 'consentId' => 9, 'value' => true, 'createdAt' => '2026-08-30 08:00:00']],
            'meta' => ['count' => 1, 'total' => 1],
        ], JSON_THROW_ON_ERROR);
        $http = new RecordingHttpClient(
            new Response(200, [], $details),
            new Response(204),
            new Response(200, [], '{"login":"ada"}'),
            new Response(200, [], $consents),
            new Response(200, [], $parents),
            new Response(200, [], $parent),
            new Response(200, [], '{"id":7}'),
            new Response(200, [], $details),
            new Response(204),
            new Response(200, [], '{"login":"ann"}'),
            new Response(200, [], $consents),
        );
        $students = $this->client($http)->withAccessToken('access-token')->students();

        self::assertSame('London', $students->details(42)->address?->city);
        $students->updateDetails(42, UpdateDetailsRequest::make()->email('new@example.test')->birthDate('1815-12-10'));
        self::assertSame('ada', $students->access(42)->login);
        self::assertTrue($students->consents(42, ListConsentsRequest::make()->sortBy('-id'))->data[0]->value);
        self::assertSame('Ann', $students->parents(42)->data[0]->name);
        self::assertSame(7, $students->parent(42, 7)->id);
        self::assertSame(7, $students->updateParent(42, 7, UpdateParentRequest::make()->firstName('Anne'))->id);
        self::assertSame('Ada Lovelace', $students->parentDetails(42, 7)->billing?->name);
        $students->updateParentDetails(42, 7, UpdateDetailsRequest::make()->mobile('123'));
        self::assertSame('ann', $students->parentAccess(42, 7)->login);
        self::assertSame(9, $students->parentConsents(42, 7)->data[0]->consentId);

        self::assertSame('/api/v4/students/42/details', $http->requests[0]->getUri()->getPath());
        self::assertSame(
            '{"email":"new@example.test","birthDate":"1815-12-10"}',
            (string) $http->requests[1]->getBody(),
        );
        self::assertSame('sortBy=-id', $http->requests[3]->getUri()->getQuery());
        self::assertSame('/api/v4/students/42/parents/7/consents', $http->requests[10]->getUri()->getPath());
    }

    public function test_it_updates_a_student_without_dropping_false(): void
    {
        $http = new RecordingHttpClient(new Response(200, [], '[]'));
        $client = $this->client($http)->withAccessToken('access-token');

        $response = $client->students()->update(
            42,
            UpdateStudentRequest::make()->hasParentAccounts(false),
        );

        self::assertSame([], get_object_vars($response));
        self::assertNotNull($http->lastRequest);
        self::assertSame('PATCH', $http->lastRequest->getMethod());
        self::assertSame('/api/v4/students/42', $http->lastRequest->getUri()->getPath());
        self::assertSame('{"hasParentAccounts":false}', (string) $http->lastRequest->getBody());
    }

    public function test_meeting_link_settings_require_a_url(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CreateGroupSettingRequest::make()->onlineLessonProvider(OnlineLessonProvider::MeetingLink);
    }

    public function test_request_builders_reject_invalid_values(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ListStudentsRequest::make()->perPage(101);
    }

    public function test_student_operations_reject_invalid_ids(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this
            ->client(new RecordingHttpClient(new Response(200, [], '[]')))
            ->withAccessToken('access-token')
            ->students()
            ->delete(0);
    }

    public function test_update_request_requires_at_least_one_field(): void
    {
        $this->expectException(\LogicException::class);

        UpdateStudentRequest::make()->toArray();
    }

    public function test_user_search_rejects_invalid_email(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SearchUsersRequest::make()->email('not-an-email');
    }

    public function test_webhook_request_rejects_invalid_urls(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CreateWebhookRequest::make()->url('javascript:alert(1)');
    }

    private function client(ClientInterface $httpClient): Client
    {
        $factory = new HttpFactory;

        return new Client(
            'https://school.langlion.com/api/v4',
            $httpClient,
            $factory,
            $factory,
        );
    }
}

final class RecordingHttpClient implements ClientInterface
{
    public ?RequestInterface $lastRequest = null;

    /** @var list<RequestInterface> */
    public array $requests = [];

    /** @var list<ResponseInterface> */
    private array $responses;

    public function __construct(ResponseInterface ...$responses)
    {
        $this->responses = array_values($responses);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;
        $this->requests[] = $request;

        return array_shift($this->responses) ?? throw new \LogicException('No queued response.');
    }
}
