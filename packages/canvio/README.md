# Canvio

Canvio is a framework-agnostic PHP 8.4 client for the [Canvas LMS REST API](https://developerdocs.instructure.com/services/canvas). It uses PSR-18 and PSR-17 interfaces, so the application chooses the HTTP client and factories.

## Features

- Bearer-token authentication against any Canvas LMS instance.
- Typed account, course, assignment, submission, enrollment, and user contexts.
- Typed request objects with discoverable fields and enums for reads and writes.
- Lazy pagination that follows Canvas `Link` headers across all pages.
- Safe pagination links that cannot forward the access token to another origin.
- Nested Canvas form encoding, including repeated `include[]` and other array parameters.
- Raw `request()` and `paginate()` access for API resources without a typed wrapper.
- Support for numeric, `self`, and prefixed SIS resource identifiers.
- No Laravel, framework, or concrete HTTP client dependency.

## Installation

```bash
composer require phpinnacle/canvio
```

Install any PSR-18 client with compatible PSR-17 factories. This example uses Guzzle:

```php
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use PHPinnacle\Canvio\Client;

$factory = new HttpFactory();
$canvas = new Client(
    'https://school.instructure.com/api/v1',
    new HttpClient(),
    $factory,
    $factory,
    'canvas-access-token',
);
```

The base URI must use HTTPS and end with `/api/v1`. Canvio sends the access token only through the `Authorization: Bearer` header.

## Resource contexts

List every course available to the current token. Canvio follows Canvas pagination lazily, requesting the next page only when iteration reaches it:

```php
use PHPinnacle\Canvio\Request\Courses\ListCoursesRequest;

foreach ($canvas->courses(new ListCoursesRequest(
    include: ['term', 'teachers'],
    perPage: 100,
)) as $course) {
    echo $course->id . ': ' . $course->name;
}
```

Singular methods bind an identifier once and return a context for related operations:

```php
$course = $canvas->course(42);
$details = $course->details();
$people = $course->users();
$enrollments = $course->enrollments();
$assignments = $course->assignments();

$assignment = $course->assignment(7);
$submissions = $assignment->submissions();
$studentSubmission = $assignment->submission(25)->details();

$profile = $canvas->user()->profile();
```

Each response exposes stable common fields and the complete decoded payload through `raw`, so optional Canvas includes remain available without making the client brittle.

Canvas SIS identifiers may be used wherever the API accepts them:

```php
$course = $canvas->course('sis_course_id:PHY-101')->details();
$user = $canvas->user('sis_user_id:student-42')->details();
```

## Account context

Account-wide operations do not require iterating through individual courses:

```php
use PHPinnacle\Canvio\Enum\EnrollmentRole;
use PHPinnacle\Canvio\Request\Courses\ListAccountCoursesRequest;
use PHPinnacle\Canvio\Request\Users\ListAccountUsersRequest;

$account = $canvas->account($accountId);
$details = $account->details();
$students = $account->students(new ListAccountUsersRequest(perPage: 100));
$users = $account->users(new ListAccountUsersRequest(searchTerm: 'Ada'));
$courses = $account->courses(new ListAccountCoursesRequest(
    published: true,
    enrollmentTypes: [EnrollmentRole::Student],
));
$subAccounts = $account->subAccounts(recursive: true);
```

## Writes

Write methods accept typed request objects. Named arguments expose every supported field directly in the IDE, while enums constrain Canvas values:

```php
use PHPinnacle\Canvio\Enum\AssignmentSubmissionType;
use PHPinnacle\Canvio\Enum\EnrollmentState;
use PHPinnacle\Canvio\Enum\EnrollmentType;
use PHPinnacle\Canvio\Request\Assignments\CreateAssignmentRequest;
use PHPinnacle\Canvio\Request\Enrollments\CreateEnrollmentRequest;
use PHPinnacle\Canvio\Request\Submissions\UpdateSubmissionRequest;

$course = $canvas->course(42);
$assignment = $course->createAssignment(new CreateAssignmentRequest(
    name: 'Final essay',
    submissionTypes: [AssignmentSubmissionType::OnlineTextEntry],
    pointsPossible: 100,
    published: true,
));

$enrollment = $course->enroll(new CreateEnrollmentRequest(
    userId: 25,
    type: EnrollmentType::Student,
    state: EnrollmentState::Active,
));

$submission = $course->assignment($assignment->id)->submission(25)->update(new UpdateSubmissionRequest(
    postedGrade: '95',
    textComment: 'Good work',
));
```

Create and update operations use separate request classes where their required fields differ. The generic `request()` method remains available for uncommon or newly introduced Canvas fields that do not yet have a typed request property.

## Other Canvas resources

Use `request()` for a single JSON response and `paginate()` for a paginated list:

```php
$module = $canvas->request('GET', '/courses/42/modules/3');

foreach ($canvas->paginate('/courses/42/modules', ['include' => ['items']]) as $module) {
    echo $module['name'];
}
```

Paths are relative to the configured `/api/v1` base URI and must not contain a query string. Pass query parameters separately so they are encoded consistently.

OAuth authorization flows and multipart file uploads are not wrapped in the initial release. Applications may obtain a token independently and use Canvas's documented multi-step upload flow with their HTTP client.

## Errors

Non-successful responses throw `PHPinnacle\Canvio\Exception\ApiException` with the status code, raw response body, decoded response, and the first Canvas error message when available. Invalid JSON, unexpected pagination shapes, and unsafe pagination links throw `UnexpectedResponseException`.

## Testing

Run the package tests from the monorepo root:

```bash
vendor/bin/phpunit -c packages/canvio/phpunit.xml.dist
```

## License

The MIT License (MIT). See [License File](LICENSE.md).
