# Langcat

Langcat is a framework-agnostic PHP 8.4 client for LangLion API v4. It depends only on PSR HTTP interfaces, so the application chooses the HTTP client and PSR-17 implementation.

## Installation

```bash
composer require phpinnacle/langcat
```

## Usage

```php
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use PHPinnacle\Langcat\Client;
use PHPinnacle\Langcat\Enum\StudentType;
use PHPinnacle\Langcat\Request\Authorization\AuthenticateRequest;
use PHPinnacle\Langcat\Request\Students\ListStudentsRequest;

$factory = new HttpFactory();
$client = new Client(
    'https://your-instance.langlion.com/api/v4',
    new HttpClient(),
    $factory,
    $factory,
);

$token = $client->authorization()->authenticate(
    AuthenticateRequest::make()
        ->clientId('client-id')
        ->clientSecret('client-secret'),
);

$students = $client
    ->withAccessToken($token->accessToken)
    ->students()
    ->all(
        ListStudentsRequest::make()
            ->page(1)
            ->perPage(20)
            ->type(StudentType::Lead)
            ->archived(false),
    );
```

Requests use fluent builders and validate caller input. Successful API payloads are returned as typed `readonly` response objects. Non-successful HTTP responses throw `ApiException` and retain the status, raw body, and typed LangLion error when available.

Request and response namespaces mirror API domains, for example `Request\Students`, `Response\Groups`, and `Response\Finances`. Cross-domain DTOs live under `Request\Shared` and `Response\Shared`.

## Runnable examples

The [examples](examples) directory contains standalone CLI scripts using Guzzle. In your local package checkout, install the example HTTP client if it is not already available:

```bash
composer require --dev guzzlehttp/guzzle guzzlehttp/psr7
```

Run the examples from the `langcat` package root with PHP 8.4 or later. Configure credentials with access to the selected LangLion resources:

```bash
export LANGLION_BASE_URI='https://your-instance.langlion.com/api/v4'
export LANGLION_CLIENT_ID='replace-with-your-client-id'
export LANGLION_CLIENT_SECRET='replace-with-your-client-secret'
```

The shared [bootstrap](examples/bootstrap.php) loads the package's Composer autoloader, or the monorepo autoloader when working in this repository. It authenticates through `/token` once per run and passes the access token to `withAccessToken()`. Tokens remain in memory and are not printed or saved. Environment variables are read directly; `.env` files are not loaded.

| Example | Purpose | LangLion changes |
| --- | --- | --- |
| [list-students.php](examples/list-students.php) | Export non-archived regular students from one school across all pages. | None beyond authentication. |
| [student-profile.php](examples/student-profile.php) | Combine a student's identity with available contact details. | None beyond authentication. |
| [group-lessons.php](examples/group-lessons.php) | Export a group's lessons starting within a date range across all pages. | None beyond authentication. |
| [create-lead.php](examples/create-lead.php) | Create a lead in a school without parent accounts. | Creates a new student record on each run. |
| [assign-student.php](examples/assign-student.php) | Assign an existing student to a group with an explicit enrollment date. | Writes the group assignment. |

Replace the sample IDs with positive numeric IDs from your instance:

```bash
php examples/list-students.php 1 > students.jsonl
php examples/student-profile.php 387
php examples/group-lessons.php 12 '2026-09-01' '2026-10-01' > lessons.jsonl
```

List examples print one JSON object per line and hold only one page in memory. Langcat returns individual pages; the scripts request subsequent pages using `meta.total` and the configured page size. Results use ascending ID order. The lesson range includes its start boundary and excludes its end boundary; date-only values use the API's date interpretation. For an explicit timezone, supply timestamps such as `2026-09-01T00:00:00+02:00`.

Write examples print result identifiers as JSON. Use a test school and group when trying them:

```bash
php examples/create-lead.php 1 'Ada' 'Example'
php examples/assign-student.php 12 387 '2026-09-07'
```

The creation response provides a student ID that can be used with `student-profile.php`. Leads are excluded by the regular-student filter in `list-students.php`. For assignment, choose an existing student eligible for the target group; the example does not change their student type. The enrollment date must use `YYYY-MM-DD`. Assignment success may have an empty response body, so the script prints the submitted identifiers only after the request succeeds.

API and transport errors propagate as exceptions and terminate the scripts with a nonzero exit status. Read examples can produce partial output before a later page fails.

## Supported operations

All 125 operations from the LangLion API v4 specification are implemented:

- `POST /token`
- `POST /token/refresh`
- `GET /me`
- `GET /students`
- `GET /students/{id}`
- `POST /students`
- `PATCH /students/{id}`
- `DELETE /students/{id}`
- `POST /students/{id}/archive`
- `POST /students/{id}/restore`
- `POST /students/{id}/schools`
- `GET /students/search`
- `GET|PATCH /students/{id}/details`
- `GET /students/{id}/access`
- `GET /students/{id}/consents`
- `GET /students/{id}/parents`
- `GET|PATCH /students/{id}/parents/{parentId}`
- `GET|PATCH /students/{id}/parents/{parentId}/details`
- `GET /students/{id}/parents/{parentId}/access`
- `GET /students/{id}/parents/{parentId}/consents`
- `GET /administrators`
- `GET /administrators/{id}`
- `GET /administrators/{id}/details`
- `GET /classrooms`
- `GET /classrooms/{id}`
- `GET /companies`
- `GET /companies/{id}`
- `GET /companies/{id}/details`
- `GET /companies/{id}/access`
- `GET /schools`
- `GET /schools/{id}`
- `GET /teachers`
- `GET /teachers/{id}`
- `GET|PATCH /teachers/{id}/details`
- `GET /teachers/{id}/consents`
- `GET /users/search`
- `GET /events`
- `GET /events/{id}`
- `GET /documents/templates`
- `GET /documents/templates/{id}`
- `GET|POST /webhooks`
- `GET|DELETE /webhooks/{id}`
- `GET|POST /groups`
- `GET /groups/search`
- `GET|PATCH /groups/{id}`
- `GET|POST /groups/{id}/lessons`
- `GET|PATCH /groups/{id}/lessons/{lessonId}`
- `GET /groups/{id}/lessons/{lessonId}/attendance`
- `GET|POST /groups/{id}/students`
- `GET /groups/{id}/students/{studentId}`
- `PATCH /groups/{id}/students/{studentId}/assign-type`
- `GET /groups/{id}/students/{studentId}/grades`
- `GET /groups/{id}/students/{studentId}/grades/collections`
- `GET /groups/{id}/teachers`
- `GET /groups/{id}/teachers/{teacherId}`
- `GET /groups/settings/courses`
- `GET /groups/settings/courses/{id}`
- `GET /groups/settings/levels`
- `GET /groups/settings/levels/{id}`
- `GET /groups/settings/lesson-statuses`
- `GET /groups/settings/lesson-statuses/{id}`
- `GET /groups/settings/group-types`
- `GET /groups/settings/group-types/{id}`
- `GET /groups/settings/grades/collections`
- `GET /groups/settings/grades/collections/{id}`
- `GET /groups/settings/grades/collections/{collectionId}/items`
- `GET /groups/settings/grades/collections/{collectionId}/items/{id}`
- `GET /groups/settings/course-books`
- `GET /groups/settings/course-books/{id}`
- `GET /groups/settings/attendance-statuses`
- `GET /groups/settings/attendance-statuses/{id}`
- `GET /groups/settings/lesson-details`
- `GET /groups/settings/lesson-details/{id}`
- `GET|POST /groups/settings/programs/collections`
- `GET|PATCH|DELETE /groups/settings/programs/collections/{id}`
- `GET|POST /groups/settings/programs/collections/{id}/items`
- `GET|PATCH|DELETE /groups/settings/programs/collections/{id}/items/{itemId}`
- `POST /groups/settings/programs/collections/{id}/items/set-orders`
- `GET /groups/settings/languages`
- `GET /groups/settings/languages/{id}`
- `GET /groups/settings/subjects`
- `GET /groups/settings/subjects/{id}`
- `GET|POST /groups/{id}/settings`
- `GET|PATCH /groups/{id}/settings/{settingId}`
- `GET /groups/settings/age-groups`
- `GET /groups/settings/age-groups/{id}`
- `GET|POST /finances/settings/installments/collections`
- `GET|PATCH|DELETE /finances/settings/installments/collections/{id}`
- `GET|POST /finances/settings/installments/collections/{collectionId}/items`
- `GET|PATCH|DELETE /finances/settings/installments/collections/{collectionId}/items/{id}`
- `PATCH /finances/settings/installments/collections/{collectionId}/items/{id}/tags`
- `GET|POST /finances/settings/installments/tags`
- `GET|PATCH /finances/settings/installments/tags/{id}`
- `GET|POST /groups/{id}/students/{studentId}/installments`
- `GET|PATCH /groups/{id}/students/{studentId}/installments/{installmentId}`
- `PATCH /groups/{id}/students/{studentId}/installments/{installmentId}/tags`

The package does not register Laravel service providers or resolve dependencies from a container.
