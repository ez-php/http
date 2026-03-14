<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Class RequestTest
 *
 * @package Tests\Http
 */
#[CoversClass(Request::class)]
final class RequestTest extends TestCase
{
    /**
     * @return Request
     */
    private function makeRequest(): Request
    {
        return new Request(
            method: 'POST',
            uri: '/users',
            query: ['page' => '2'],
            body: ['name' => 'John'],
            headers: ['content-type' => 'application/json'],
            cookies: ['session' => 'abc123'],
            server: ['SERVER_NAME' => 'localhost'],
            rawBody: '{"name":"John"}',
        );
    }

    /**
     * @return void
     */
    public function test_method_returns_method(): void
    {
        $this->assertSame('POST', $this->makeRequest()->method());
    }

    /**
     * @return void
     */
    public function test_uri_returns_uri(): void
    {
        $this->assertSame('/users', $this->makeRequest()->uri());
    }

    /**
     * @return void
     */
    public function test_query_returns_value(): void
    {
        $this->assertSame('2', $this->makeRequest()->query('page'));
    }

    /**
     * @return void
     */
    public function test_query_returns_default_when_missing(): void
    {
        $this->assertNull($this->makeRequest()->query('missing'));
    }

    /**
     * @return void
     */
    public function test_input_returns_value(): void
    {
        $this->assertSame('John', $this->makeRequest()->input('name'));
    }

    /**
     * @return void
     */
    public function test_input_returns_default_when_missing(): void
    {
        $this->assertSame('fallback', $this->makeRequest()->input('missing', 'fallback'));
    }

    /**
     * @return void
     */
    public function test_header_returns_value(): void
    {
        $this->assertSame('application/json', $this->makeRequest()->header('content-type'));
    }

    /**
     * @return void
     */
    public function test_header_is_case_insensitive(): void
    {
        $this->assertSame('application/json', $this->makeRequest()->header('Content-Type'));
    }

    /**
     * @return void
     */
    public function test_header_returns_default_when_missing(): void
    {
        $this->assertNull($this->makeRequest()->header('x-missing'));
    }

    /**
     * @return void
     */
    public function test_cookie_returns_value(): void
    {
        $this->assertSame('abc123', $this->makeRequest()->cookie('session'));
    }

    /**
     * @return void
     */
    public function test_cookie_returns_default_when_missing(): void
    {
        $this->assertNull($this->makeRequest()->cookie('missing'));
    }

    /**
     * @return void
     */
    public function test_server_returns_value(): void
    {
        $this->assertSame('localhost', $this->makeRequest()->server('SERVER_NAME'));
    }

    /**
     * @return void
     */
    public function test_server_returns_default_when_missing(): void
    {
        $this->assertNull($this->makeRequest()->server('MISSING'));
    }

    /**
     * @return void
     */
    public function test_raw_body_returns_body(): void
    {
        $this->assertSame('{"name":"John"}', $this->makeRequest()->rawBody());
    }

    /**
     * @return void
     */
    public function test_param_returns_null_by_default(): void
    {
        $this->assertNull($this->makeRequest()->param('id'));
    }

    /**
     * @return void
     */
    public function test_param_returns_default_when_missing(): void
    {
        $this->assertSame('fallback', $this->makeRequest()->param('missing', 'fallback'));
    }

    /**
     * @return void
     */
    public function test_with_params_returns_new_request_with_params(): void
    {
        $request = $this->makeRequest()->withParams(['id' => '7']);
        $this->assertSame('7', $request->param('id'));
    }

    /**
     * @return void
     */
    public function test_with_params_preserves_original_request_data(): void
    {
        $original = $this->makeRequest();
        $with = $original->withParams(['id' => '7']);

        $this->assertSame('POST', $with->method());
        $this->assertSame('/users', $with->uri());
        $this->assertSame('2', $with->query('page'));
        $this->assertNull($original->param('id'));
    }

    /**
     * @return void
     */
    public function test_with_method_returns_new_request_with_method(): void
    {
        $request = $this->makeRequest()->withMethod('PATCH');
        $this->assertSame('PATCH', $request->method());
    }

    /**
     * @return void
     */
    public function test_with_method_preserves_original_request_data(): void
    {
        $original = $this->makeRequest();
        $with = $original->withMethod('DELETE');

        $this->assertSame('/users', $with->uri());
        $this->assertSame('2', $with->query('page'));
        $this->assertSame('POST', $original->method());
    }
}
