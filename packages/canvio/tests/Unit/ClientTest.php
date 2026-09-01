<?php

namespace PHPinnacle\Canvio\Tests\Unit;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPinnacle\Canvio\Client;
use PHPinnacle\Canvio\Enum\AccountUserSort;
use PHPinnacle\Canvio\Enum\AssignmentGradingType;
use PHPinnacle\Canvio\Enum\AssignmentSubmissionType;
use PHPinnacle\Canvio\Enum\EnrollmentRole;
use PHPinnacle\Canvio\Enum\EnrollmentState;
use PHPinnacle\Canvio\Enum\EnrollmentTask;
use PHPinnacle\Canvio\Enum\EnrollmentType;
use PHPinnacle\Canvio\Enum\SortOrder;
use PHPinnacle\Canvio\Enum\SubmissionType;
use PHPinnacle\Canvio\Exception\ApiException;
use PHPinnacle\Canvio\Exception\UnexpectedResponseException;
use PHPinnacle\Canvio\Request\Assignments\CreateAssignmentRequest;
use PHPinnacle\Canvio\Request\Assignments\UpdateAssignmentRequest;
use PHPinnacle\Canvio\Request\Courses\ListAccountCoursesRequest;
use PHPinnacle\Canvio\Request\Courses\ListCoursesRequest;
use PHPinnacle\Canvio\Request\Enrollments\CreateEnrollmentRequest;
use PHPinnacle\Canvio\Request\Submissions\SubmitSubmissionRequest;
use PHPinnacle\Canvio\Request\Submissions\UpdateSubmissionRequest;
use PHPinnacle\Canvio\Request\Users\ListAccountUsersRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ClientTest extends TestCase
{
    public function test_it_creates_and_updates_core_resources_with_canvas_form_encoding(): void
    {
        $assignment = [
            'id' => 7,
            'course_id' => 42,
            'name' => 'Essay',
            'submission_types' => ['online_text_entry'],
            'published' => false,
        ];
        $enrollment = [
            'id' => 9,
            'type' => 'StudentEnrollment',
            'enrollment_state' => 'active',
            'user_id' => 25,
            'course_id' => 42,
        ];
        $submission = [
            'assignment_id' => 7,
            'user_id' => 25,
            'workflow_state' => 'graded',
            'score' => 95,
            'grade' => '95',
        ];
        $http = new RecordingHttpClient(
            new Response(200, [], json_encode($assignment, JSON_THROW_ON_ERROR)),
            new Response(200, [], json_encode($assignment, JSON_THROW_ON_ERROR)),
            new Response(200, [], json_encode($enrollment, JSON_THROW_ON_ERROR)),
            new Response(200, [], json_encode($enrollment, JSON_THROW_ON_ERROR)),
            new Response(200, [], json_encode($submission, JSON_THROW_ON_ERROR)),
            new Response(200, [], json_encode($submission, JSON_THROW_ON_ERROR)),
        );
        $client = $this->client($http);
        $course = $client->course(42);
        $assignmentContext = $course->assignment(7);

        self::assertSame(
            'Essay',
            $course->createAssignment(new CreateAssignmentRequest(
                name: 'Essay',
                submissionTypes: [AssignmentSubmissionType::OnlineTextEntry],
                published: false,
            ))->name,
        );
        $assignmentContext->update(new UpdateAssignmentRequest(
            pointsPossible: 100,
            gradingType: AssignmentGradingType::Points,
        ));
        self::assertSame(
            25,
            $course->enroll(new CreateEnrollmentRequest(
                userId: 25,
                type: EnrollmentType::Student,
                state: EnrollmentState::Active,
            ))->userId,
        );
        $course->enrollment(9)->end(EnrollmentTask::Deactivate);
        $assignmentContext->submit(new SubmitSubmissionRequest(
            type: SubmissionType::OnlineTextEntry,
            body: '<p>Essay</p>',
            textComment: 'Draft',
        ));
        self::assertSame(
            95,
            $assignmentContext
                ->submission(25)
                ->update(new UpdateSubmissionRequest(
                    postedGrade: '95',
                    textComment: 'Good work',
                ))
                ->score,
        );

        self::assertSame(
            'assignment%5Bname%5D=Essay&assignment%5Bsubmission_types%5D%5B%5D=online_text_entry'
            . '&assignment%5Bpublished%5D=0',
            (string) $http->requests[0]->getBody(),
        );
        self::assertSame(
            'assignment%5Bpoints_possible%5D=100&assignment%5Bgrading_type%5D=points',
            (string) $http->requests[1]->getBody(),
        );
        self::assertSame(
            'enrollment%5Buser_id%5D=25&enrollment%5Btype%5D=StudentEnrollment'
            . '&enrollment%5Benrollment_state%5D=active',
            (string) $http->requests[2]->getBody(),
        );
        self::assertSame('task=deactivate', $http->requests[3]->getUri()->getQuery());
        self::assertSame(
            'submission%5Bsubmission_type%5D=online_text_entry&submission%5Bbody%5D=%3Cp%3EEssay%3C%2Fp%3E'
            . '&comment%5Btext_comment%5D=Draft',
            (string) $http->requests[4]->getBody(),
        );
        self::assertSame(
            'submission%5Bposted_grade%5D=95&comment%5Btext_comment%5D=Good%20work',
            (string) $http->requests[5]->getBody(),
        );
        self::assertSame([
            '/api/v1/courses/42/assignments',
            '/api/v1/courses/42/assignments/7',
            '/api/v1/courses/42/enrollments',
            '/api/v1/courses/42/enrollments/9',
            '/api/v1/courses/42/assignments/7/submissions',
            '/api/v1/courses/42/assignments/7/submissions/25',
        ], array_map(
            static fn (RequestInterface $request) => $request->getUri()->getPath(),
            $http->requests,
        ));
    }

    public function test_it_follows_canvas_link_pagination_and_returns_typed_courses(): void
    {
        $http = new RecordingHttpClient(
            new Response(
                200,
                [
                    'Link' => '<https://school.instructure.com/api/v1/courses?page=2&per_page=1>; rel="next"',
                ],
                '[{"id":1,"name":"First"}]',
            ),
            new Response(200, [], '[{"id":2,"name":"Second"}]'),
        );
        $courses = iterator_to_array($this
            ->client($http)
            ->courses(new ListCoursesRequest(
                include: ['term'],
                perPage: 1,
            )));

        self::assertSame(['First', 'Second'], array_map(
            static fn ($course) => $course->name,
            $courses,
        ));
        self::assertSame('include%5B%5D=term&per_page=1', $http->requests[0]->getUri()->getQuery());
        self::assertSame('page=2&per_page=1', $http->requests[1]->getUri()->getQuery());
        self::assertSame('Bearer access-token', $http->requests[1]->getHeaderLine('Authorization'));
    }

    public function test_it_preserves_canvas_error_details(): void
    {
        $client = $this->client(new RecordingHttpClient(
            new Response(403, [], '{"errors":[{"message":"Permission denied"}]}'),
        ));

        try {
            $client->user()->profile();
            self::fail('Expected an API exception.');
        } catch (ApiException $exception) {
            self::assertSame(403, $exception->statusCode);
            self::assertSame('Permission denied', $exception->getMessage());
            self::assertSame('Permission denied', $exception->response['errors'][0]['message']);
        }
    }

    public function test_it_rejects_cross_origin_pagination_links(): void
    {
        $client = $this->client(new RecordingHttpClient(
            new Response(
                200,
                [
                    'Link' => '<https://attacker.example/api/v1/courses?page=2>; rel="next"',
                ],
                '[{"id":1,"name":"First"}]',
            ),
        ));

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('unsafe pagination link');

        iterator_to_array($client->courses());
    }

    public function test_it_rejects_empty_assignment_names(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name cannot be empty');

        new CreateAssignmentRequest(name: '  ');
    }

    public function test_it_scopes_users_courses_and_sub_accounts_to_an_account(): void
    {
        $http = new RecordingHttpClient(
            new Response(200, [], '{"id":1,"name":"School"}'),
            new Response(200, [], '[{"id":25,"name":"Student"}]'),
            new Response(200, [], '[{"id":42,"name":"Physics"}]'),
            new Response(200, [], '[{"id":2,"name":"Campus","parent_account_id":1}]'),
        );
        $account = $this->client($http)->account(1);

        self::assertSame('School', $account->details()->name);
        self::assertSame(
            'Student',
            iterator_to_array($account->students(new ListAccountUsersRequest(
                searchTerm: 'Student',
                sort: AccountUserSort::Id,
                order: SortOrder::Ascending,
                perPage: 100,
            )))[0]->name,
        );
        self::assertSame(
            'Physics',
            iterator_to_array($account->courses(new ListAccountCoursesRequest(
                published: true,
                enrollmentTypes: [EnrollmentRole::Student],
                perPage: 100,
            )))[0]->name,
        );
        self::assertSame('Campus', iterator_to_array($account->subAccounts(recursive: true))[0]->name);

        self::assertSame(
            'enrollment_type=student&search_term=Student&sort=id&order=asc&per_page=100',
            $http->requests[1]->getUri()->getQuery(),
        );
        self::assertSame(
            'enrollment_type%5B%5D=student&published=1&per_page=100',
            $http->requests[2]->getUri()->getQuery(),
        );
        self::assertSame('recursive=1', $http->requests[3]->getUri()->getQuery());
        self::assertSame([
            '/api/v1/accounts/1',
            '/api/v1/accounts/1/users',
            '/api/v1/accounts/1/courses',
            '/api/v1/accounts/1/sub_accounts',
        ], array_map(
            static fn (RequestInterface $request) => $request->getUri()->getPath(),
            $http->requests,
        ));
    }

    public function test_it_supports_canvas_sis_identifiers(): void
    {
        $http = new RecordingHttpClient(new Response(200, [], '{"id":42,"name":"Physics"}'));

        $this->client($http)->course('sis_course_id:PHY-101')->details();

        self::assertSame(
            '/api/v1/courses/sis_course_id%3APHY-101',
            $http->lastRequest?->getUri()->getPath(),
        );
    }

    public function test_it_validates_the_api_base_uri(): void
    {
        $factory = new HttpFactory;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must end with /api/v1');

        new Client(
            'https://school.instructure.com',
            new RecordingHttpClient,
            $factory,
            $factory,
            'access-token',
        );
    }

    private function client(ClientInterface $httpClient): Client
    {
        $factory = new HttpFactory;

        return new Client(
            'https://school.instructure.com/api/v1',
            $httpClient,
            $factory,
            $factory,
            'access-token',
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
