<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\HeaderValidator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Class HeaderValidatorTest
 *
 * @package Tests\Http
 */
#[CoversClass(HeaderValidator::class)]
final class HeaderValidatorTest extends TestCase
{
    /**
     * @return void
     */
    public function test_accepts_a_normal_header(): void
    {
        HeaderValidator::assertValid('Content-Type', 'text/html; charset=UTF-8');

        $this->expectNotToPerformAssertions();
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function invalidHeaders(): array
    {
        return [
            'CR in name' => ["X-Evil\r", 'v', 'Invalid header name: contains a control character.'],
            'LF in name' => ["X-Evil\n", 'v', 'Invalid header name: contains a control character.'],
            'CRLF injection in value' => ['X-Ok', "v\r\nSet-Cookie: a=b", 'Invalid header value: contains a control character.'],
            'NUL in value' => ['X-Ok', "v\0", 'Invalid header value: contains a control character.'],
            'DEL in value' => ['X-Ok', "v\x7F", 'Invalid header value: contains a control character.'],
        ];
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $message
     *
     * @return void
     */
    #[DataProvider('invalidHeaders')]
    public function test_rejects_control_characters(string $name, string $value, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        HeaderValidator::assertValid($name, $value);
    }
}
