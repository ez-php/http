<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\Request;
use EzPhp\Http\UploadedFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

/**
 * Class RequestTest
 *
 * @package Tests\Http
 */
#[CoversClass(Request::class)]
#[UsesClass(UploadedFile::class)]
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

    // ── ip ────────────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_ip_returns_remote_addr(): void
    {
        $request = new Request(method: 'GET', uri: '/', server: ['REMOTE_ADDR' => '1.2.3.4']);

        $this->assertSame('1.2.3.4', $request->ip());
    }

    /**
     * @return void
     */
    public function test_ip_returns_empty_string_when_remote_addr_missing(): void
    {
        $request = new Request(method: 'GET', uri: '/');

        $this->assertSame('', $request->ip());
    }

    /**
     * @return void
     */
    public function test_ip_ignores_x_forwarded_for_when_no_trusted_proxies(): void
    {
        $request = new Request(
            method: 'GET',
            uri: '/',
            headers: ['x-forwarded-for' => '9.9.9.9'],
            server: ['REMOTE_ADDR' => '1.2.3.4'],
        );

        $this->assertSame('1.2.3.4', $request->ip());
    }

    /**
     * @return void
     */
    public function test_ip_ignores_x_forwarded_for_when_remote_addr_not_trusted(): void
    {
        $request = new Request(
            method: 'GET',
            uri: '/',
            headers: ['x-forwarded-for' => '9.9.9.9'],
            server: ['REMOTE_ADDR' => '1.2.3.4'],
        );

        $this->assertSame('1.2.3.4', $request->ip(['10.0.0.1']));
    }

    /**
     * @return void
     */
    public function test_ip_returns_x_forwarded_for_when_remote_addr_is_trusted(): void
    {
        $request = new Request(
            method: 'GET',
            uri: '/',
            headers: ['x-forwarded-for' => '9.9.9.9'],
            server: ['REMOTE_ADDR' => '10.0.0.1'],
        );

        $this->assertSame('9.9.9.9', $request->ip(['10.0.0.1']));
    }

    /**
     * @return void
     */
    public function test_ip_returns_first_ip_from_x_forwarded_for_chain(): void
    {
        $request = new Request(
            method: 'GET',
            uri: '/',
            headers: ['x-forwarded-for' => '9.9.9.9, 10.0.0.2, 10.0.0.3'],
            server: ['REMOTE_ADDR' => '10.0.0.1'],
        );

        $this->assertSame('9.9.9.9', $request->ip(['10.0.0.1']));
    }

    /**
     * @return void
     */
    public function test_ip_falls_back_to_remote_addr_when_trusted_but_xff_absent(): void
    {
        $request = new Request(
            method: 'GET',
            uri: '/',
            server: ['REMOTE_ADDR' => '10.0.0.1'],
        );

        $this->assertSame('10.0.0.1', $request->ip(['10.0.0.1']));
    }

    /**
     * @return void
     */
    public function test_ip_falls_back_to_remote_addr_when_xff_is_not_a_valid_ip(): void
    {
        $request = new Request(
            method: 'GET',
            uri: '/',
            headers: ['x-forwarded-for' => 'not-an-ip'],
            server: ['REMOTE_ADDR' => '10.0.0.1'],
        );

        $this->assertSame('10.0.0.1', $request->ip(['10.0.0.1']));
    }

    // ── has / hasQuery / hasInput ─────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_has_returns_true_when_key_exists_in_query(): void
    {
        $request = new Request(method: 'GET', uri: '/', query: ['page' => '2']);

        $this->assertTrue($request->has('page'));
    }

    /**
     * @return void
     */
    public function test_has_returns_true_when_key_exists_in_body(): void
    {
        $request = new Request(method: 'POST', uri: '/', body: ['name' => 'John']);

        $this->assertTrue($request->has('name'));
    }

    /**
     * @return void
     */
    public function test_has_returns_true_when_key_value_is_null(): void
    {
        $request = new Request(method: 'POST', uri: '/', body: ['field' => null]);

        $this->assertTrue($request->has('field'));
    }

    /**
     * @return void
     */
    public function test_has_returns_false_when_key_absent(): void
    {
        $request = new Request(method: 'GET', uri: '/');

        $this->assertFalse($request->has('missing'));
    }

    /**
     * @return void
     */
    public function test_has_query_returns_true_when_key_exists_in_query(): void
    {
        $request = new Request(method: 'GET', uri: '/', query: ['sort' => 'asc']);

        $this->assertTrue($request->hasQuery('sort'));
    }

    /**
     * @return void
     */
    public function test_has_query_returns_false_when_key_only_in_body(): void
    {
        $request = new Request(method: 'POST', uri: '/', body: ['sort' => 'asc']);

        $this->assertFalse($request->hasQuery('sort'));
    }

    /**
     * @return void
     */
    public function test_has_query_returns_true_when_key_value_is_null(): void
    {
        $request = new Request(method: 'GET', uri: '/', query: ['q' => null]);

        $this->assertTrue($request->hasQuery('q'));
    }

    /**
     * @return void
     */
    public function test_has_input_returns_true_when_key_exists_in_body(): void
    {
        $request = new Request(method: 'POST', uri: '/', body: ['email' => 'a@b.com']);

        $this->assertTrue($request->hasInput('email'));
    }

    /**
     * @return void
     */
    public function test_has_input_returns_false_when_key_only_in_query(): void
    {
        $request = new Request(method: 'GET', uri: '/', query: ['email' => 'a@b.com']);

        $this->assertFalse($request->hasInput('email'));
    }

    /**
     * @return void
     */
    public function test_has_input_returns_true_when_key_value_is_null(): void
    {
        $request = new Request(method: 'POST', uri: '/', body: ['opt' => null]);

        $this->assertTrue($request->hasInput('opt'));
    }

    // ── all ───────────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_all_merges_query_and_body(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/',
            query: ['page' => '2', 'sort' => 'asc'],
            body: ['name' => 'John'],
        );

        $this->assertSame(['page' => '2', 'sort' => 'asc', 'name' => 'John'], $request->all());
    }

    /**
     * @return void
     */
    public function test_all_body_takes_precedence_over_query_on_collision(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/',
            query: ['name' => 'query-value'],
            body: ['name' => 'body-value'],
        );

        $this->assertSame(['name' => 'body-value'], $request->all());
    }

    /**
     * @return void
     */
    public function test_all_returns_empty_array_when_no_query_or_body(): void
    {
        $request = new Request(method: 'GET', uri: '/');

        $this->assertSame([], $request->all());
    }

    /**
     * @return void
     */
    public function test_all_returns_only_query_when_body_is_empty(): void
    {
        $request = new Request(method: 'GET', uri: '/', query: ['q' => 'search']);

        $this->assertSame(['q' => 'search'], $request->all());
    }

    /**
     * @return void
     */
    public function test_all_returns_only_body_when_query_is_empty(): void
    {
        $request = new Request(method: 'POST', uri: '/', body: ['email' => 'a@b.com']);

        $this->assertSame(['email' => 'a@b.com'], $request->all());
    }

    // ── file ─────────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_file_returns_uploaded_file_for_known_key(): void
    {
        $file = new UploadedFile('photo.jpg', 'image/jpeg', 1024, '/tmp/phpXXX', UPLOAD_ERR_OK);
        $request = new Request(method: 'POST', uri: '/', files: ['avatar' => $file]);

        $this->assertSame($file, $request->file('avatar'));
    }

    /**
     * @return void
     */
    public function test_file_returns_null_for_missing_key(): void
    {
        $request = new Request(method: 'POST', uri: '/');

        $this->assertNull($request->file('avatar'));
    }

    /**
     * @return void
     */
    public function test_with_params_preserves_files(): void
    {
        $file = new UploadedFile('doc.pdf', 'application/pdf', 512, '/tmp/phpYYY', UPLOAD_ERR_OK);
        $request = new Request(method: 'POST', uri: '/', files: ['attachment' => $file]);

        $new = $request->withParams(['id' => '1']);

        $this->assertSame($file, $new->file('attachment'));
    }

    /**
     * @return void
     */
    public function test_with_method_preserves_files(): void
    {
        $file = new UploadedFile('doc.pdf', 'application/pdf', 512, '/tmp/phpZZZ', UPLOAD_ERR_OK);
        $request = new Request(method: 'POST', uri: '/', files: ['attachment' => $file]);

        $new = $request->withMethod('PUT');

        $this->assertSame($file, $new->file('attachment'));
    }

    // ── JSON body auto-parsing ────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_input_parses_json_body_when_content_type_is_json(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/',
            headers: ['content-type' => 'application/json'],
            rawBody: '{"name":"Alice","age":30}',
        );

        $this->assertSame('Alice', $request->input('name'));
        $this->assertSame(30, $request->input('age'));
    }

    /**
     * @return void
     */
    public function test_all_merges_json_body_with_query(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/',
            query: ['page' => '2'],
            headers: ['content-type' => 'application/json'],
            rawBody: '{"name":"Bob"}',
        );

        $this->assertSame(['page' => '2', 'name' => 'Bob'], $request->all());
    }

    /**
     * @return void
     */
    public function test_json_body_takes_precedence_over_query_on_collision(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/',
            query: ['name' => 'query-value'],
            headers: ['content-type' => 'application/json'],
            rawBody: '{"name":"json-value"}',
        );

        $this->assertSame(['name' => 'json-value'], $request->all());
    }

    /**
     * @return void
     */
    public function test_has_returns_true_for_key_in_json_body(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/',
            headers: ['content-type' => 'application/json'],
            rawBody: '{"token":"abc"}',
        );

        $this->assertTrue($request->has('token'));
    }

    /**
     * @return void
     */
    public function test_has_input_returns_true_for_key_in_json_body(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/',
            headers: ['content-type' => 'application/json'],
            rawBody: '{"token":"abc"}',
        );

        $this->assertTrue($request->hasInput('token'));
    }

    /**
     * @return void
     */
    public function test_explicit_body_takes_precedence_over_json_raw_body(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/',
            body: ['name' => 'form-value'],
            headers: ['content-type' => 'application/json'],
            rawBody: '{"name":"json-value"}',
        );

        $this->assertSame('form-value', $request->input('name'));
    }

    /**
     * @return void
     */
    public function test_json_parsing_skipped_without_json_content_type(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/',
            rawBody: '{"name":"Alice"}',
        );

        $this->assertNull($request->input('name'));
    }

    /**
     * @return void
     */
    public function test_invalid_json_raw_body_returns_empty(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/',
            headers: ['content-type' => 'application/json'],
            rawBody: '{invalid-json',
        );

        $this->assertNull($request->input('name'));
        $this->assertSame([], $request->all());
    }
}
