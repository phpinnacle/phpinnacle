<?php

namespace PHPinnacle\Porta\Tests\Unit;

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use PHPinnacle\Porta\Enums\IntegrationAuth;
use PHPinnacle\Porta\Models\Integration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class IntegrationTest extends TestCase
{
    public function test_it_authorizes_a_matching_header_secret(): void
    {
        $integration = $this->integration('secret');

        $integration->authorize(Request::create('/', server: ['HTTP_X_WEBHOOK_SECRET' => 'secret']));

        $this->addToAssertionCount(1);
    }

    public function test_it_rejects_an_invalid_header_secret(): void
    {
        $this->expectException(HttpException::class);

        $this->integration('secret')->authorize(
            Request::create('/', server: ['HTTP_X_WEBHOOK_SECRET' => 'invalid']),
        );
    }

    public function test_it_rejects_a_missing_header_and_configured_secret(): void
    {
        $this->expectException(HttpException::class);

        $this->integration(null)->authorize(Request::create('/'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance(new Application);
    }

    protected function tearDown(): void
    {
        Container::setInstance();

        parent::tearDown();
    }

    private function integration(#[\SensitiveParameter] ?string $secret): Integration
    {
        $integration = new Integration;
        $integration->auth = IntegrationAuth::Header;
        $integration->auth_key = 'X-Webhook-Secret';
        $integration->auth_secret = $secret;

        return $integration;
    }
}
