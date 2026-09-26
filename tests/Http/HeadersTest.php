<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\Headers;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Class HeadersTest
 *
 * @package Tests\Http
 */
#[CoversClass(Headers::class)]
final class HeadersTest extends TestCase
{
    /**
     * @return void
     */
    public function test_set_adds_a_new_header(): void
    {
        $this->assertSame(['X-A' => '1', 'X-B' => '2'], Headers::set(['X-A' => '1'], 'X-B', '2'));
    }

    /**
     * @return void
     */
    public function test_set_replaces_an_existing_header_regardless_of_case(): void
    {
        $headers = Headers::set(['Content-Type' => 'text/html', 'X-A' => '1'], 'content-type', 'application/json');

        $this->assertSame(['X-A' => '1', 'content-type' => 'application/json'], $headers);
    }

    /**
     * @return void
     */
    public function test_get_is_case_insensitive(): void
    {
        $headers = ['content-type' => 'application/json'];

        $this->assertSame('application/json', Headers::get($headers, 'Content-Type'));
        $this->assertNull(Headers::get($headers, 'X-Missing'));
    }

    /**
     * @return void
     */
    public function test_normalize_keeps_the_last_of_case_insensitive_duplicates(): void
    {
        $headers = Headers::normalize(['X-A' => '1', 'x-a' => '2', 'X-B' => '3']);

        $this->assertSame(['x-a' => '2', 'X-B' => '3'], $headers);
    }
}
