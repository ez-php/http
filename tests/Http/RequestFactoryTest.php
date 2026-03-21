<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\Request;
use EzPhp\Http\RequestFactory;
use EzPhp\Http\UploadedFile;
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
#[UsesClass(UploadedFile::class)]
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
        $_FILES = [];
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

    /**
     * @return void
     */
    public function test_create_from_globals_extracts_uploaded_file(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/upload';
        $_FILES['avatar'] = [
            'name' => 'photo.jpg',
            'type' => 'image/jpeg',
            'size' => 2048,
            'tmp_name' => '/tmp/phpXXX',
            'error' => UPLOAD_ERR_OK,
        ];

        $request = RequestFactory::createFromGlobals();
        $file = $request->file('avatar');

        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertSame('photo.jpg', $file->originalName());
        $this->assertSame('image/jpeg', $file->mimeType());
        $this->assertSame(2048, $file->size());
        $this->assertTrue($file->isValid());
    }

    /**
     * @return void
     */
    public function test_create_from_globals_excludes_files_with_no_file_error(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/upload';
        $_FILES['avatar'] = [
            'name' => '',
            'type' => '',
            'size' => 0,
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
        ];

        $request = RequestFactory::createFromGlobals();

        $this->assertNull($request->file('avatar'));
    }
}
