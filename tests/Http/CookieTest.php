<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\Cookie;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Class CookieTest
 *
 * @package Tests\Http
 */
#[CoversClass(Cookie::class)]
final class CookieTest extends TestCase
{
    /**
     * @return void
     */
    public function test_accessors_return_constructor_values(): void
    {
        $cookie = new Cookie('session', 'abc', 3600, '/app', 'example.com', true, true);

        $this->assertSame('session', $cookie->name());
        $this->assertSame('abc', $cookie->value());
        $this->assertSame(3600, $cookie->ttl());
        $this->assertSame('/app', $cookie->path());
        $this->assertSame('example.com', $cookie->domain());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
    }

    /**
     * @return void
     */
    public function test_to_header_value_contains_name_and_value(): void
    {
        $cookie = new Cookie('token', 'xyz123');

        $this->assertStringContainsString('token=xyz123', $cookie->toHeaderValue());
    }

    /**
     * @return void
     */
    public function test_to_header_value_url_encodes_name_and_value(): void
    {
        $cookie = new Cookie('my cookie', 'hello world');
        $header = $cookie->toHeaderValue();

        $this->assertStringContainsString('my+cookie=hello+world', $header);
    }

    /**
     * @return void
     */
    public function test_to_header_value_omits_max_age_when_ttl_is_zero(): void
    {
        $cookie = new Cookie('a', 'b', 0);

        $this->assertStringNotContainsString('Max-Age', $cookie->toHeaderValue());
    }

    /**
     * @return void
     */
    public function test_to_header_value_includes_max_age_when_ttl_positive(): void
    {
        $cookie = new Cookie('a', 'b', 3600);

        $this->assertStringContainsString('Max-Age=3600', $cookie->toHeaderValue());
    }

    /**
     * @return void
     */
    public function test_to_header_value_includes_path(): void
    {
        $cookie = new Cookie('a', 'b', 0, '/admin');

        $this->assertStringContainsString('Path=/admin', $cookie->toHeaderValue());
    }

    /**
     * @return void
     */
    public function test_to_header_value_omits_domain_when_empty(): void
    {
        $cookie = new Cookie('a', 'b');

        $this->assertStringNotContainsString('Domain', $cookie->toHeaderValue());
    }

    /**
     * @return void
     */
    public function test_to_header_value_includes_domain_when_set(): void
    {
        $cookie = new Cookie('a', 'b', 0, '/', 'example.com');

        $this->assertStringContainsString('Domain=example.com', $cookie->toHeaderValue());
    }

    /**
     * @return void
     */
    public function test_to_header_value_includes_secure_flag(): void
    {
        $cookie = new Cookie('a', 'b', 0, '/', '', true);

        $this->assertStringContainsString('Secure', $cookie->toHeaderValue());
    }

    /**
     * @return void
     */
    public function test_to_header_value_omits_secure_flag_when_false(): void
    {
        $cookie = new Cookie('a', 'b');

        $this->assertStringNotContainsString('Secure', $cookie->toHeaderValue());
    }

    /**
     * @return void
     */
    public function test_to_header_value_includes_http_only_flag(): void
    {
        $cookie = new Cookie('a', 'b', 0, '/', '', false, true);

        $this->assertStringContainsString('HttpOnly', $cookie->toHeaderValue());
    }

    /**
     * @return void
     */
    public function test_to_header_value_full_cookie(): void
    {
        $cookie = new Cookie('sess', 'tok', 7200, '/app', 'example.com', true, true);
        $header = $cookie->toHeaderValue();

        $this->assertStringContainsString('sess=tok', $header);
        $this->assertStringContainsString('Max-Age=7200', $header);
        $this->assertStringContainsString('Path=/app', $header);
        $this->assertStringContainsString('Domain=example.com', $header);
        $this->assertStringContainsString('Secure', $header);
        $this->assertStringContainsString('HttpOnly', $header);
    }
}
