<?php

namespace PHPinnacle\Canvio;

use Generator;
use PHPinnacle\Canvio\Context\AccountContext;
use PHPinnacle\Canvio\Context\CourseContext;
use PHPinnacle\Canvio\Context\UserContext;
use PHPinnacle\Canvio\Request\Courses\ListCoursesRequest;
use PHPinnacle\Canvio\Response\CourseResponse;
use PHPinnacle\Canvio\Support\Transport;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Client
{
    private Transport $transport;

    public function __construct(
        string $baseUri,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        #[\SensitiveParameter]
        ?string $accessToken = null,
    ) {
        $this->transport = new Transport(
            $baseUri,
            $httpClient,
            $requestFactory,
            $streamFactory,
            $accessToken,
        );
    }

    public function account(int|string $accountId): AccountContext
    {
        return new AccountContext($this->transport, $accountId);
    }

    public function course(int|string $courseId): CourseContext
    {
        return new CourseContext($this->transport, $courseId);
    }

    /** @return Generator<int, CourseResponse> */
    public function courses(?ListCoursesRequest $request = null): Generator
    {
        foreach ($this->transport->paginate('/courses', $request?->toQuery() ?? []) as $payload) {
            yield CourseResponse::fromArray($payload);
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @return iterable<int, array<array-key, mixed>>
     */
    public function paginate(string $path, array $query = []): iterable
    {
        return $this->transport->paginate($path, $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $body
     * @return array<array-key, mixed>
     */
    public function request(string $method, string $path, array $query = [], ?array $body = null): array
    {
        return $this->transport->send($method, $path, $query, $body);
    }

    public function user(int|string $userId = 'self'): UserContext
    {
        return new UserContext($this->transport, $userId);
    }

    public function withAccessToken(#[\SensitiveParameter] string $accessToken): self
    {
        $client = clone $this;
        $client->transport = $this->transport->withAccessToken($accessToken);

        return $client;
    }
}
