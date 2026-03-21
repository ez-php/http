<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\Cookie;
use EzPhp\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

/**
 * Class ResponseTest
 *
 * @package Tests\Http
 */
#[CoversClass(Response::class)]
#[UsesClass(Cookie::class)]
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

    // ── cookies ───────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_cookies_is_empty_by_default(): void
    {
        $this->assertSame([], (new Response())->cookies());
    }

    /**
     * @return void
     */
    public function test_with_cookie_returns_new_instance(): void
    {
        $response = new Response();
        $clone = $response->withCookie('session', 'abc');

        $this->assertNotSame($response, $clone);
    }

    /**
     * @return void
     */
    public function test_with_cookie_does_not_mutate_original(): void
    {
        $response = new Response();
        $response->withCookie('session', 'abc');

        $this->assertSame([], $response->cookies());
    }

    /**
     * @return void
     */
    public function test_with_cookie_adds_cookie_to_clone(): void
    {
        $response = (new Response())->withCookie('session', 'abc123');
        $cookies = $response->cookies();

        $this->assertCount(1, $cookies);
        $this->assertInstanceOf(Cookie::class, $cookies[0]);
        $this->assertSame('session', $cookies[0]->name());
        $this->assertSame('abc123', $cookies[0]->value());
    }

    /**
     * @return void
     */
    public function test_with_cookie_passes_all_options(): void
    {
        $response = (new Response())->withCookie('tok', 'val', 3600, '/api', 'example.com', true, true);
        $cookie = $response->cookies()[0];

        $this->assertSame(3600, $cookie->ttl());
        $this->assertSame('/api', $cookie->path());
        $this->assertSame('example.com', $cookie->domain());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
    }

    /**
     * @return void
     */
    public function test_multiple_cookies_can_be_chained(): void
    {
        $response = (new Response())
            ->withCookie('a', '1')
            ->withCookie('b', '2');

        $this->assertCount(2, $response->cookies());
        $this->assertSame('a', $response->cookies()[0]->name());
        $this->assertSame('b', $response->cookies()[1]->name());
    }
}
