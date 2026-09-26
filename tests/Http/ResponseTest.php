<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\Cookie;
use EzPhp\Http\Headers;
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
#[UsesClass(Headers::class)]
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
    public function test_with_header_replaces_existing_header_case_insensitively(): void
    {
        $response = (new Response())
            ->withHeader('Content-Type', 'text/html')
            ->withHeader('content-type', 'application/json');

        $this->assertSame(['content-type' => 'application/json'], $response->headers());
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

    public function test_with_header_rejects_crlf_in_header_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Response())->withHeader('X-Custom', "safe\r\nSet-Cookie: evil=1");
    }

    public function test_with_header_rejects_crlf_in_header_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Response())->withHeader("X-Custom\r\nSet-Cookie: evil=1", 'value');
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

    // ── withBody ──────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_with_body_returns_new_instance(): void
    {
        $response = new Response('original');
        $clone = $response->withBody('replaced');

        $this->assertNotSame($response, $clone);
    }

    /**
     * @return void
     */
    public function test_with_body_does_not_mutate_original(): void
    {
        $response = new Response('original');
        $response->withBody('replaced');

        $this->assertSame('original', $response->body());
    }

    /**
     * @return void
     */
    public function test_with_body_sets_new_body_on_clone(): void
    {
        $response = new Response('original');
        $clone = $response->withBody('replaced');

        $this->assertSame('replaced', $clone->body());
    }

    /**
     * @return void
     */
    public function test_with_body_preserves_status(): void
    {
        $response = new Response('body', 404);
        $clone = $response->withBody('new body');

        $this->assertSame(404, $clone->status());
    }

    /**
     * @return void
     */
    public function test_with_body_preserves_headers(): void
    {
        $response = (new Response('body'))->withHeader('Content-Type', 'text/html');
        $clone = $response->withBody('new body');

        $this->assertSame('text/html', $clone->headers()['Content-Type']);
    }

    /**
     * @return void
     */
    public function test_with_body_preserves_cookies(): void
    {
        $response = (new Response('body'))->withCookie('session', 'abc');
        $clone = $response->withBody('new body');

        $this->assertCount(1, $clone->cookies());
        $this->assertSame('session', $clone->cookies()[0]->name());
    }

    /**
     * @return void
     */
    public function test_write_body_passes_the_whole_body_in_one_call(): void
    {
        $chunks = [];

        (new Response('hello world'))->writeBody(function (string $chunk) use (&$chunks): void {
            $chunks[] = $chunk;
        });

        $this->assertSame(['hello world'], $chunks);
    }

    /**
     * @return void
     */
    public function test_write_body_on_empty_response_writes_one_empty_chunk(): void
    {
        $chunks = [];

        (new Response())->writeBody(function (string $chunk) use (&$chunks): void {
            $chunks[] = $chunk;
        });

        $this->assertSame([''], $chunks);
    }
}
