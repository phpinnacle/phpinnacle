<?php

namespace PHPinnacle\Canvio\Support;

use Generator;
use InvalidArgumentException;
use JsonException;
use LogicException;
use PHPinnacle\Canvio\Exception\ApiException;
use PHPinnacle\Canvio\Exception\UnexpectedResponseException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/** @internal */
final readonly class Transport
{
    private string $baseUri;

    /** @var array{scheme: string, host: string, port: int, path: string} */
    private array $base;

    public function __construct(
        string $baseUri,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private ?string $accessToken = null,
    ) {
        $baseUri = rtrim($baseUri, '/');
        $parts = parse_url($baseUri);

        if (
            filter_var($baseUri, FILTER_VALIDATE_URL) === false
            || !is_array($parts)
            || ($parts['scheme'] ?? null) !== 'https'
            || !is_string($parts['host'] ?? null)
        ) {
            throw new InvalidArgumentException('Canvas API base URI must be a valid HTTPS URL.');
        }

        $path = rtrim((string) ($parts['path'] ?? ''), '/');

        if (!str_ends_with($path, '/api/v1')) {
            throw new InvalidArgumentException('Canvas API base URI must end with /api/v1.');
        }

        if ($accessToken !== null && trim($accessToken) === '') {
            throw new InvalidArgumentException('Access token cannot be empty.');
        }

        $this->baseUri = $baseUri;
        $this->base = [
            'scheme' => $parts['scheme'],
            'host' => strtolower($parts['host']),
            'port' => $parts['port'] ?? 443,
            'path' => $path,
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     * @return Generator<int, array<string, mixed>>
     */
    public function paginate(string $path, array $query = []): Generator
    {
        $uri = $this->uri($path, $query);

        while ($uri !== null) {
            $exchange = $this->exchange('GET', $uri);
            $payload = $exchange['payload'];

            if (!array_is_list($payload)) {
                throw new UnexpectedResponseException(
                    $exchange['status'],
                    $exchange['body'],
                    'Canvas API pagination response must be a list.',
                );
            }

            foreach ($payload as $item) {
                if (!is_array($item)) {
                    throw new UnexpectedResponseException(
                        $exchange['status'],
                        $exchange['body'],
                        'Canvas API pagination response must contain only objects.',
                    );
                }

                yield $item;
            }

            $uri = $exchange['next'];
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $body
     * @return array<array-key, mixed>
     */
    public function send(string $method, string $path, array $query = [], ?array $body = null): array
    {
        return $this->exchange($method, $this->uri($path, $query), $body)['payload'];
    }

    public function withAccessToken(string $accessToken): self
    {
        return new self(
            $this->baseUri,
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
            $accessToken,
        );
    }

    /** @return array<array-key, mixed> */
    private function decode(ResponseInterface $response, string $body): array
    {
        if ($body === '') {
            return [];
        }

        try {
            $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedResponseException(
                $response->getStatusCode(),
                $body,
                previous: $exception,
            );
        }

        return is_array($payload)
            ? $payload
            : throw new UnexpectedResponseException($response->getStatusCode(), $body);
    }

    /** @return array<array-key, mixed>|null */
    private function errorResponse(string $body): ?array
    {
        try {
            $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);

            return is_array($payload) ? $payload : null;
        } catch (JsonException) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array{status: int, body: string, payload: array<array-key, mixed>, next: ?string}
     */
    private function exchange(string $method, string $uri, ?array $body = null): array
    {
        $accessToken = $this->accessToken ?? throw new LogicException('An access token is required for this request.');
        $request = $this->requestFactory
            ->createRequest(strtoupper($method), $uri)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Authorization', 'Bearer ' . $accessToken);

        if ($body !== null) {
            $request = $request
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($this->streamFactory->createStream(QueryEncoder::encode($body)));
        }

        $response = $this->httpClient->sendRequest($request);
        $responseBody = (string) $response->getBody();

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new ApiException(
                $response->getStatusCode(),
                $responseBody,
                $this->errorResponse($responseBody),
            );
        }

        $payload = $this->decode($response, $responseBody);

        return [
            'status' => $response->getStatusCode(),
            'body' => $responseBody,
            'payload' => $payload,
            'next' => $this->nextPage($response, $responseBody),
        ];
    }

    private function nextPage(ResponseInterface $response, string $body): ?string
    {
        $next = LinkHeader::next($response->getHeaderLine('Link'));

        if ($next === null) {
            return null;
        }

        if (str_starts_with($next, '/')) {
            $next =
                $this->base['scheme']
                . '://'
                . $this->base['host']
                . ($this->base['port'] === 443 ? '' : ':' . $this->base['port'])
                . $next;
        }

        $parts = parse_url($next);
        $path = is_array($parts) ? (string) ($parts['path'] ?? '') : '';

        if (
            !is_array($parts)
            || ($parts['scheme'] ?? null) !== $this->base['scheme']
            || strtolower((string) ($parts['host'] ?? '')) !== $this->base['host']
            || ($parts['port'] ?? 443) !== $this->base['port']
            || $path !== $this->base['path']
            && !str_starts_with($path, $this->base['path'] . '/')
        ) {
            throw new UnexpectedResponseException(
                $response->getStatusCode(),
                $body,
                'Canvas API returned an unsafe pagination link.',
            );
        }

        return $next;
    }

    /** @param array<string, mixed> $query */
    private function uri(string $path, array $query): string
    {
        if (!str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '?')) {
            throw new InvalidArgumentException('Canvas API path must be an absolute path without a query string.');
        }

        if (in_array('..', explode('/', $path), true)) {
            throw new InvalidArgumentException('Canvas API path cannot contain parent traversal.');
        }

        $uri = $this->baseUri . $path;
        $encoded = QueryEncoder::encode($query);

        return $encoded === '' ? $uri : $uri . '?' . $encoded;
    }
}
