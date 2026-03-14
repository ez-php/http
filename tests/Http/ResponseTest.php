<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Class ResponseTest
 *
 * @package Tests\Http
 */
#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    /**
     * @return void
     */
    public function test_default_status_is_200(): void
    {
        $response = new Response();
        $this->assertSame(200, $response->status());
    }

    /**
     * @return void
     */
    public function test_status_returns_given_status(): void
    {
        $response = new Response('', 404);
        $this->assertSame(404, $response->status());
    }

    /**
     * @return void
     */
    public function test_body_returns_body(): void
    {
        $response = new Response('Hello');
        $this->assertSame('Hello', $response->body());
    }

    /**
     * @return void
     */
    public function test_with_header_returns_new_instance(): void
    {
        $response = new Response('test');
        $clone = $response->withHeader('Content-Type', 'application/json');
        $this->assertNotSame($response, $clone);
    }

    /**
     * @return void
     */
    public function test_with_header_does_not_mutate_original(): void
    {
        $response = new Response('test');
        $response->withHeader('Content-Type', 'application/json');
        $this->assertEmpty($response->headers());
    }

    /**
     * @return void
     */
    public function test_headers_returns_set_headers(): void
    {
        $response = (new Response())->withHeader('Content-Type', 'text/html');
        $this->assertSame(['Content-Type' => 'text/html'], $response->headers());
    }

    /**
     * @return void
     */
    public function test_multiple_headers_can_be_chained(): void
    {
        $response = (new Response())
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-Custom', 'value');

        $this->assertSame([
            'Content-Type' => 'application/json',
            'X-Custom' => 'value',
        ], $response->headers());
    }
}
