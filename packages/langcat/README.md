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
