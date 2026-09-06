<?php

namespace PHPinnacle\Franz\Support;

use InvalidArgumentException;
use JsonException;
use PHPinnacle\Franz\Exception\ApiException;
use PHPinnacle\Franz\Exception\UnexpectedResponseException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/** @internal */
final readonly class Transport
{
    private string $baseUri;

    /** @param array<string, string> $headers */
    public function __construct(
        string $baseUri,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        #[\SensitiveParameter]
        private array $headers = [],
    ) {
        $parts = parse_url($baseUri);

        if (
            filter_var($baseUri, FILTER_VALIDATE_URL) === false
            || !is_array($parts)
            || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || ($parts['query'] ?? null) !== null
            || ($parts['fragment'] ?? null) !== null
            || ($parts['user'] ?? null) !== null
            || ($parts['pass'] ?? null) !== null
        ) {
            throw new InvalidArgumentException(
                'Kafka Connect base URI must be an HTTP(S) URL without credentials, query, or fragment.',
            );
        }

        $this->baseUri = rtrim($baseUri, '/');
    }

    /**
     * @param array<string, string|int|bool|list<string>> $query
     * @param array<string, mixed>|null $body
     * @return array<array-key, mixed>|null
     */
    public function send(string $method, string $path, array $query = [], ?array $body = null): ?array
    {
        $request = $this->requestFactory->createRequest(strtoupper($method), $this->uri($path, $query));

        foreach ($this->headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $request = $request->withHeader('Accept', 'application/json');

        if ($body !== null) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream(json_encode((object) $body, JSON_THROW_ON_ERROR)));
        }

        $response = $this->httpClient->sendRequest($request);
        $status = $response->getStatusCode();
        $responseBody = (string) $response->getBody();

        if ($status < 200 || $status >= 300) {
            throw new ApiException($status, $responseBody, $this->errorResponse($responseBody));
        }

        if ($responseBody === '') {
            return null;
        }

        try {
            $payload = json_decode($responseBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedResponseException($status, $responseBody, previous: $exception);
        }

        return is_array($payload)
            ? $payload
            : throw new UnexpectedResponseException($status, $responseBody);
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

    /** @param array<string, string|int|bool|list<string>> $query */
    private function uri(string $path, array $query): string
    {
        if (
            !str_starts_with($path, '/')
            || str_starts_with($path, '//')
            || strpbrk($path, '?#') !== false
            || in_array('..', explode('/', rawurldecode($path)), true)
        ) {
            throw new InvalidArgumentException(
                'Kafka Connect path must be absolute without a query, fragment, or parent traversal.',
            );
        }

        $parameters = [];

        foreach ($query as $name => $values) {
            foreach (is_array($values) ? $values : [$values] as $value) {
                $parameters[] =
                    rawurlencode($name)
                    . '='
                    . rawurlencode(match ($value) {
                        true => 'true',
                        false => 'false',
                        default => (string) $value,
                    });
            }
        }

        return $this->baseUri . $path . ($parameters === [] ? '' : '?' . implode('&', $parameters));
    }
}
