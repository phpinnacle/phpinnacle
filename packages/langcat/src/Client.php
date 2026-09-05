<?php

namespace PHPinnacle\Langcat;

use PHPinnacle\Langcat\Api\AdministratorsApi;
use PHPinnacle\Langcat\Api\AuthorizationApi;
use PHPinnacle\Langcat\Api\ClassroomsApi;
use PHPinnacle\Langcat\Api\CompaniesApi;
use PHPinnacle\Langcat\Api\DocumentsApi;
use PHPinnacle\Langcat\Api\EventsApi;
use PHPinnacle\Langcat\Api\FinancesApi;
use PHPinnacle\Langcat\Api\GroupsApi;
use PHPinnacle\Langcat\Api\GroupSettingsApi;
use PHPinnacle\Langcat\Api\SchoolsApi;
use PHPinnacle\Langcat\Api\StudentsApi;
use PHPinnacle\Langcat\Api\TeachersApi;
use PHPinnacle\Langcat\Api\UsersApi;
use PHPinnacle\Langcat\Api\WebhooksApi;
use PHPinnacle\Langcat\Support\Transport;
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

    public function withAccessToken(#[\SensitiveParameter] string $accessToken): self
    {
        $client = clone $this;
        $client->transport = $this->transport->withAccessToken($accessToken);

        return $client;
    }

    public function authorization(): AuthorizationApi
    {
        return new AuthorizationApi($this->transport);
    }

    public function students(): StudentsApi
    {
        return new StudentsApi($this->transport);
    }

    public function administrators(): AdministratorsApi
    {
        return new AdministratorsApi($this->transport);
    }

    public function classrooms(): ClassroomsApi
    {
        return new ClassroomsApi($this->transport);
    }

    public function companies(): CompaniesApi
    {
        return new CompaniesApi($this->transport);
    }

    public function schools(): SchoolsApi
    {
        return new SchoolsApi($this->transport);
    }

    public function teachers(): TeachersApi
    {
        return new TeachersApi($this->transport);
    }

    public function users(): UsersApi
    {
        return new UsersApi($this->transport);
    }

    public function documents(): DocumentsApi
    {
        return new DocumentsApi($this->transport);
    }

    public function events(): EventsApi
    {
        return new EventsApi($this->transport);
    }

    public function webhooks(): WebhooksApi
    {
        return new WebhooksApi($this->transport);
    }

    public function groups(): GroupsApi
    {
        return new GroupsApi($this->transport);
    }

    public function groupSettings(): GroupSettingsApi
    {
        return new GroupSettingsApi($this->transport);
    }

    public function finances(): FinancesApi
    {
        return new FinancesApi($this->transport);
    }
}
