<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Strict typed input accessors: a value comes back only when it is exactly the
 * requested type (or its unambiguous string form from a form body).
 *
 * @package Tests\Http
 */
#[CoversClass(Request::class)]
final class RequestTypedInputTest extends TestCase
{
    /**
     * @param array<string, mixed> $body
     */
    private function request(array $body): Request
    {
        return new Request('POST', '/', body: $body);
    }

    /**
     * @return array<string, array{mixed, int|null}>
     */
    public static function integers(): array
    {
        return [
            'int' => [12, 12],
            'negative int' => [-3, -3],
            'zero' => [0, 0],
            'digit string' => ['12', 12],
            'negative string' => ['-7', -7],
            'trailing garbage' => ['12abc', null],
            'leading zero' => ['012', null],
            'whitespace' => [' 12', null],
            'plus sign' => ['+12', null],
            'float' => [12.0, null],
            'decimal string' => ['12.5', null],
            'exponent string' => ['1e3', null],
            'bool' => [true, null],
            'array' => [[1], null],
            'overflow' => ['99999999999999999999', null],
            'empty string' => ['', null],
        ];
    }

    #[DataProvider('integers')]
    public function test_integer(mixed $value, ?int $expected): void
    {
        self::assertSame($expected, $this->request(['v' => $value])->integer('v'));
    }

    /**
     * @return array<string, array{mixed, bool|null}>
     */
    public static function booleans(): array
    {
        return [
            'true' => [true, true],
            'false' => [false, false],
            'string true' => ['true', true],
            'string false' => ['false', false],
            'string 1' => ['1', true],
            'string 0' => ['0', false],
            'int 1' => [1, true],
            'int 0' => [0, false],
            'yes' => ['yes', null],
            'on' => ['on', null],
            'int 2' => [2, null],
            'uppercase' => ['TRUE', null],
            'null' => [null, null],
        ];
    }

    #[DataProvider('booleans')]
    public function test_boolean(mixed $value, ?bool $expected): void
    {
        self::assertSame($expected, $this->request(['v' => $value])->boolean('v'));
    }

    /**
     * @return array<string, array{mixed, int|null, string|null}>
     */
    public static function decimals(): array
    {
        return [
            'int' => [12, 2, '12.00'],
            'string' => ['12.5', 2, '12.50'],
            'exact scale' => ['0.05', 2, '0.05'],
            'negative' => ['-1.25', 2, '-1.25'],
            'float' => [0.1, 2, '0.10'],
            'too many decimals' => ['1.005', 2, null],
            'no scale keeps digits' => ['3.14159', null, '3.14159'],
            'scale zero' => ['7', 0, '7'],
            'scale zero rejects fraction' => ['7.5', 0, null],
            'garbage' => ['12abc', 2, null],
            'exponent' => ['1e3', 2, null],
            'comma' => ['1,5', 2, null],
            'leading dot' => ['.5', 2, null],
            'bool' => [true, 2, null],
            'empty' => ['', 2, null],
        ];
    }

    #[DataProvider('decimals')]
    public function test_decimal(mixed $value, ?int $scale, ?string $expected): void
    {
        self::assertSame($expected, $this->request(['v' => $value])->decimal('v', scale: $scale));
    }

    public function test_missing_keys_return_null(): void
    {
        $request = $this->request([]);

        self::assertNull($request->integer('missing'));
        self::assertNull($request->boolean('missing'));
        self::assertNull($request->decimal('missing'));
    }

    public function test_values_come_from_a_json_body(): void
    {
        $request = new Request(
            'POST',
            '/',
            headers: ['Content-Type' => 'application/json'],
            rawBody: '{"qty":3,"gift":false,"price":"19.9"}',
        );

        self::assertSame(3, $request->integer('qty'));
        self::assertFalse($request->boolean('gift'));
        self::assertSame('19.90', $request->decimal('price', scale: 2));
    }
}
