<?php

namespace PHPinnacle\Langcat\Support;

use InvalidArgumentException;
use JsonException;
use LogicException;
use PHPinnacle\Langcat\Exception\ApiException;
use PHPinnacle\Langcat\Exception\UnexpectedResponseException;
use PHPinnacle\Langcat\Response\Shared\ErrorResponse;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use UnexpectedValueException;

/** @internal */
final readonly class Transport
{
    private string $baseUri;

    public function __construct(
        string $baseUri,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        #[\SensitiveParameter]
        private ?string $accessToken = null,
    ) {
        $baseUri = rtrim($baseUri, '/');

        if (filter_var($baseUri, FILTER_VALIDATE_URL) === false || parse_url($baseUri, PHP_URL_SCHEME) !== 'https') {
            throw new InvalidArgumentException('LangLion base URI must be a valid HTTPS URL.');
        }

        if ($accessToken !== null && trim($accessToken) === '') {
            throw new InvalidArgumentException('Access token cannot be empty.');
        }

        $this->baseUri = $baseUri;
    }

    public function withAccessToken(#[\SensitiveParameter] string $accessToken): self
    {
        return new self(
            $this->baseUri,
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
            $accessToken,
        );
    }

    /**
     * @param  array<string, int|string>  $query
     * @param  array<array-key, mixed>|null  $body
     * @return array<array-key, mixed>
     */
    public function send(
        string $method,
        string $path,
        array $query = [],
        ?array $body = null,
        TransportMode $mode = TransportMode::Json,
    ): array {
        $uri = $this->baseUri . $path;

        if ($query !== []) {
            $uri .= '?' . http_build_query($query, encoding_type: PHP_QUERY_RFC3986);
        }

        $request = $this->requestFactory
            ->createRequest($method, $uri)
            ->withHeader('Accept', 'application/json');

        if ($mode !== TransportMode::AnonymousForm) {
            $accessToken = $this->accessToken ?? throw new LogicException(
                'An access token is required for this request.',
            );
            $request = $request->withHeader('Authorization', 'Bearer ' . $accessToken);
        }

        if ($body !== null) {
            $content = $mode === TransportMode::AnonymousForm
                ? http_build_query($body, encoding_type: PHP_QUERY_RFC3986)
                : json_encode($body, JSON_THROW_ON_ERROR);
            $request = $request
                ->withHeader(
                    'Content-Type',
                    $mode === TransportMode::AnonymousForm ? 'application/x-www-form-urlencoded' : 'application/json',
                )
                ->withBody($this->streamFactory->createStream($content));
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

        if ($responseBody === '' && $mode === TransportMode::EmptyResponse) {
            return [];
        }

        try {
            $payload = json_decode($responseBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedResponseException($response->getStatusCode(), $responseBody, $exception);
        }

        return is_array($payload)
            ? $payload
            : throw new UnexpectedResponseException($response->getStatusCode(), $responseBody);
    }

    private function errorResponse(string $body): ?ErrorResponse
    {
        try {
            $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);

            return is_array($payload) ? ErrorResponse::fromArray($payload) : null;
        } catch (JsonException|UnexpectedValueException) {
            return null;
        }
    }
}
