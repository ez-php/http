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

    // ── static factory methods ─────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_json_encodes_data_and_sets_content_type(): void
    {
        $response = Response::json(['key' => 'value']);

        $this->assertSame(200, $response->status());
        $this->assertSame('{"key":"value"}', $response->body());
        $this->assertSame('application/json', $response->headers()['Content-Type']);
    }

    /**
     * @return void
     */
    public function test_json_accepts_custom_status(): void
    {
        $response = Response::json(['error' => 'not found'], 404);
        $this->assertSame(404, $response->status());
    }

    /**
     * @return void
     */
    public function test_redirect_sets_location_header_and_302_status(): void
    {
        $response = Response::redirect('/dashboard');

        $this->assertSame(302, $response->status());
        $this->assertSame('/dashboard', $response->headers()['Location']);
        $this->assertSame('', $response->body());
    }

    /**
     * @return void
     */
    public function test_redirect_accepts_custom_status(): void
    {
        $response = Response::redirect('/login', 301);
        $this->assertSame(301, $response->status());
    }

    /**
     * @return void
     */
    public function test_html_sets_body_and_content_type(): void
    {
        $response = Response::html('<h1>Hello</h1>');

        $this->assertSame(200, $response->status());
        $this->assertSame('<h1>Hello</h1>', $response->body());
        $this->assertSame('text/html; charset=UTF-8', $response->headers()['Content-Type']);
    }

    /**
     * @return void
     */
    public function test_html_accepts_custom_status(): void
    {
        $response = Response::html('<p>Error</p>', 422);
        $this->assertSame(422, $response->status());
    }

    /**
     * @return void
     */
    public function test_no_content_returns_204_with_empty_body(): void
    {
        $response = Response::noContent();

        $this->assertSame(204, $response->status());
        $this->assertSame('', $response->body());
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
}
