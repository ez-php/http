<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Class RequestContentNegotiationTest
 *
 * @package Tests\Http
 */
#[CoversClass(Request::class)]
final class RequestContentNegotiationTest extends TestCase
{
    // ── contentType ──────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_content_type_returns_header_value(): void
    {
        $request = new Request('POST', '/', headers: ['content-type' => 'application/json']);
        $this->assertSame('application/json', $request->contentType());
    }

    /**
     * @return void
     */
    public function test_content_type_returns_null_when_absent(): void
    {
        $request = new Request('GET', '/');
        $this->assertNull($request->contentType());
    }

    // ── isJson ───────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_is_json_returns_true_for_json_content_type(): void
    {
        $request = new Request('POST', '/', headers: ['content-type' => 'application/json']);
        $this->assertTrue($request->isJson());
    }

    /**
     * @return void
     */
    public function test_is_json_returns_true_for_json_content_type_with_charset(): void
    {
        $request = new Request('POST', '/', headers: ['content-type' => 'application/json; charset=UTF-8']);
        $this->assertTrue($request->isJson());
    }

    /**
     * @return void
     */
    public function test_is_json_returns_false_for_form_content_type(): void
    {
        $request = new Request('POST', '/', headers: ['content-type' => 'application/x-www-form-urlencoded']);
        $this->assertFalse($request->isJson());
    }

    /**
     * @return void
     */
    public function test_is_json_returns_false_when_no_content_type(): void
    {
        $request = new Request('POST', '/');
        $this->assertFalse($request->isJson());
    }

    // ── acceptsJson ──────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_accepts_json_returns_true_for_application_json(): void
    {
        $request = new Request('GET', '/', headers: ['accept' => 'application/json']);
        $this->assertTrue($request->acceptsJson());
    }

    /**
     * @return void
     */
    public function test_accepts_json_returns_true_for_wildcard(): void
    {
        $request = new Request('GET', '/', headers: ['accept' => '*/*']);
        $this->assertTrue($request->acceptsJson());
    }

    /**
     * @return void
     */
    public function test_accepts_json_returns_true_for_mixed_accept_header(): void
    {
        $request = new Request('GET', '/', headers: ['accept' => 'text/html, application/json']);
        $this->assertTrue($request->acceptsJson());
    }

    /**
     * @return void
     */
    public function test_accepts_json_returns_false_for_html_only(): void
    {
        $request = new Request('GET', '/', headers: ['accept' => 'text/html']);
        $this->assertFalse($request->acceptsJson());
    }

    /**
     * @return void
     */
    public function test_accepts_json_returns_false_when_no_accept_header(): void
    {
        $request = new Request('GET', '/');
        $this->assertFalse($request->acceptsJson());
    }

    // ── wantsJson ────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_wants_json_returns_true_when_content_type_is_json(): void
    {
        $request = new Request('POST', '/', headers: ['content-type' => 'application/json']);
        $this->assertTrue($request->wantsJson());
    }

    /**
     * @return void
     */
    public function test_wants_json_returns_true_when_accept_is_json(): void
    {
        $request = new Request('GET', '/', headers: ['accept' => 'application/json']);
        $this->assertTrue($request->wantsJson());
    }

    /**
     * @return void
     */
    public function test_wants_json_returns_false_when_neither_header_indicates_json(): void
    {
        $request = new Request('GET', '/', headers: ['accept' => 'text/html']);
        $this->assertFalse($request->wantsJson());
    }

    // ── accepts ──────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_accepts_returns_true_for_matching_type(): void
    {
        $request = new Request('GET', '/', headers: ['accept' => 'text/html, application/json']);
        $this->assertTrue($request->accepts('text/html'));
    }

    /**
     * @return void
     */
    public function test_accepts_returns_true_for_wildcard(): void
    {
        $request = new Request('GET', '/', headers: ['accept' => '*/*']);
        $this->assertTrue($request->accepts('text/html'));
    }

    /**
     * @return void
     */
    public function test_accepts_returns_false_for_non_matching_type(): void
    {
        $request = new Request('GET', '/', headers: ['accept' => 'application/json']);
        $this->assertFalse($request->accepts('text/html'));
    }

    /**
     * @return void
     */
    public function test_accepts_returns_false_when_no_accept_header(): void
    {
        $request = new Request('GET', '/');
        $this->assertFalse($request->accepts('text/html'));
    }
}
