<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\Request;
use EzPhp\Http\RequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

/**
 * Class RequestFactoryTest
 *
 * @package Tests\Http
 */
#[CoversClass(RequestFactory::class)]
#[UsesClass(Request::class)]
final class RequestFactoryTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_SERVER = [];
    }

    /**
     * @return void
     */
    public function test_create_from_globals_uses_server_method(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/submit';

        $request = RequestFactory::createFromGlobals();

        $this->assertSame('POST', $request->method());
        $this->assertSame('/submit', $request->uri());
    }

    /**
     * @return void
     */
    public function test_create_from_globals_defaults_to_get_when_method_missing(): void
    {
        $_SERVER['REQUEST_URI'] = '/';

        $request = RequestFactory::createFromGlobals();

        $this->assertSame('GET', $request->method());
    }

    /**
     * @return void
     */
    public function test_create_from_globals_defaults_to_slash_when_uri_missing(): void
    {
        $request = RequestFactory::createFromGlobals();
        $this->assertSame('/', $request->uri());
    }

    /**
     * @return void
     */
    public function test_create_from_globals_extracts_http_headers(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $request = RequestFactory::createFromGlobals();

        $this->assertSame('application/json', $request->header('accept'));
    }

    /**
     * @return void
     */
    public function test_create_from_globals_extracts_content_type(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['CONTENT_TYPE'] = 'application/json';

        $request = RequestFactory::createFromGlobals();

        $this->assertSame('application/json', $request->header('content-type'));
    }

    /**
     * @return void
     */
    public function test_create_from_globals_extracts_content_length(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['CONTENT_LENGTH'] = '42';

        $request = RequestFactory::createFromGlobals();

        $this->assertSame('42', $request->header('content-length'));
    }
}
