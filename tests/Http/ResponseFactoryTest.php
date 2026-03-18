<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\Response;
use EzPhp\Http\ResponseFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

/**
 * Class ResponseFactoryTest
 *
 * @package Tests\Http
 */
#[CoversClass(ResponseFactory::class)]
#[UsesClass(Response::class)]
final class ResponseFactoryTest extends TestCase
{
    // ── json ─────────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_json_encodes_array_as_body(): void
    {
        $response = ResponseFactory::json(['key' => 'value']);
        $this->assertSame('{"key":"value"}', $response->body());
    }

    /**
     * @return void
     */
    public function test_json_sets_content_type_header(): void
    {
        $response = ResponseFactory::json([]);
        $this->assertSame('application/json', $response->headers()['Content-Type']);
    }

    /**
     * @return void
     */
    public function test_json_default_status_is_200(): void
    {
        $response = ResponseFactory::json([]);
        $this->assertSame(200, $response->status());
    }

    /**
     * @return void
     */
    public function test_json_accepts_custom_status(): void
    {
        $response = ResponseFactory::json(['error' => 'not found'], 404);
        $this->assertSame(404, $response->status());
    }

    /**
     * @return void
     */
    public function test_json_encodes_null(): void
    {
        $response = ResponseFactory::json(null);
        $this->assertSame('null', $response->body());
    }

    // ── html ─────────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_html_sets_body(): void
    {
        $response = ResponseFactory::html('<h1>Hello</h1>');
        $this->assertSame('<h1>Hello</h1>', $response->body());
    }

    /**
     * @return void
     */
    public function test_html_sets_content_type_header(): void
    {
        $response = ResponseFactory::html('');
        $this->assertSame('text/html; charset=UTF-8', $response->headers()['Content-Type']);
    }

    /**
     * @return void
     */
    public function test_html_default_status_is_200(): void
    {
        $response = ResponseFactory::html('');
        $this->assertSame(200, $response->status());
    }

    /**
     * @return void
     */
    public function test_html_accepts_custom_status(): void
    {
        $response = ResponseFactory::html('<h1>Error</h1>', 500);
        $this->assertSame(500, $response->status());
    }

    // ── text ─────────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_text_sets_body(): void
    {
        $response = ResponseFactory::text('Hello world');
        $this->assertSame('Hello world', $response->body());
    }

    /**
     * @return void
     */
    public function test_text_sets_content_type_header(): void
    {
        $response = ResponseFactory::text('');
        $this->assertSame('text/plain; charset=UTF-8', $response->headers()['Content-Type']);
    }

    /**
     * @return void
     */
    public function test_text_default_status_is_200(): void
    {
        $response = ResponseFactory::text('');
        $this->assertSame(200, $response->status());
    }

    // ── redirect ─────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_redirect_sets_location_header(): void
    {
        $response = ResponseFactory::redirect('/home');
        $this->assertSame('/home', $response->headers()['Location']);
    }

    /**
     * @return void
     */
    public function test_redirect_default_status_is_302(): void
    {
        $response = ResponseFactory::redirect('/home');
        $this->assertSame(302, $response->status());
    }

    /**
     * @return void
     */
    public function test_redirect_accepts_custom_status(): void
    {
        $response = ResponseFactory::redirect('/home', 301);
        $this->assertSame(301, $response->status());
    }

    /**
     * @return void
     */
    public function test_redirect_body_is_empty(): void
    {
        $response = ResponseFactory::redirect('/home');
        $this->assertSame('', $response->body());
    }

    // ── noContent ────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_no_content_status_is_204(): void
    {
        $response = ResponseFactory::noContent();
        $this->assertSame(204, $response->status());
    }

    /**
     * @return void
     */
    public function test_no_content_body_is_empty(): void
    {
        $response = ResponseFactory::noContent();
        $this->assertSame('', $response->body());
    }

    /**
     * @return void
     */
    public function test_no_content_has_no_headers(): void
    {
        $response = ResponseFactory::noContent();
        $this->assertEmpty($response->headers());
    }
}
