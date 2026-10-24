<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * String-backed enum fixture for Request::enum().
 */
enum RequestTypedInputSlot: string
{
    case Weapon = 'weapon';
    case Armor = 'armor';
}

/**
 * Int-backed enum fixture for Request::enum().
 */
enum RequestTypedInputRarity: int
{
    case Common = 1;
    case Rare = 3;
}

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

    /**
     * @return array<string, array{mixed, RequestTypedInputSlot|null}>
     */
    public static function stringEnums(): array
    {
        return [
            'matching value' => ['weapon', RequestTypedInputSlot::Weapon],
            'unknown value' => ['ring', null],
            'case name is not the value' => ['Weapon', null],
            'int' => [1, null],
            'bool' => [true, null],
            'array' => [['weapon'], null],
            'empty' => ['', null],
        ];
    }

    #[DataProvider('stringEnums')]
    public function test_enum_with_string_backing(mixed $value, ?RequestTypedInputSlot $expected): void
    {
        self::assertSame($expected, $this->request(['v' => $value])->enum('v', RequestTypedInputSlot::class));
    }

    /**
     * @return array<string, array{mixed, RequestTypedInputRarity|null}>
     */
    public static function intEnums(): array
    {
        return [
            'int' => [3, RequestTypedInputRarity::Rare],
            'digit string from a form body' => ['1', RequestTypedInputRarity::Common],
            'unknown int' => [2, null],
            'leading zero' => ['03', null],
            'float' => [3.0, null],
            'name' => ['Rare', null],
            'bool' => [true, null],
        ];
    }

    #[DataProvider('intEnums')]
    public function test_enum_with_int_backing(mixed $value, ?RequestTypedInputRarity $expected): void
    {
        self::assertSame($expected, $this->request(['v' => $value])->enum('v', RequestTypedInputRarity::class));
    }

    /**
     * @return array<string, array{mixed, int|null}>
     */
    public static function nonBodyIntegers(): array
    {
        return [
            'digit string' => ['42', 42],
            'negative string' => ['-1', -1],
            'int' => [7, 7],
            'leading zero' => ['007', null],
            'trailing garbage' => ['42abc', null],
            'decimal string' => ['4.2', null],
            'overflow' => ['99999999999999999999', null],
            'array' => [['1'], null],
            'empty' => ['', null],
        ];
    }

    #[DataProvider('nonBodyIntegers')]
    public function test_param_integer(mixed $value, ?int $expected): void
    {
        self::assertSame($expected, new Request('GET', '/', params: ['id' => $value])->paramInteger('id'));
    }

    #[DataProvider('nonBodyIntegers')]
    public function test_query_integer(mixed $value, ?int $expected): void
    {
        self::assertSame($expected, new Request('GET', '/', query: ['page' => $value])->queryInteger('page'));
    }

    public function test_integer_accessors_read_only_their_own_source(): void
    {
        $request = new Request('POST', '/', query: ['q' => '1'], body: ['b' => '2'], params: ['p' => '3']);

        self::assertNull($request->integer('q'));
        self::assertNull($request->queryInteger('b'));
        self::assertNull($request->paramInteger('q'));
        self::assertSame(3, $request->paramInteger('p'));
    }

    public function test_missing_keys_return_null(): void
    {
        $request = $this->request([]);

        self::assertNull($request->integer('missing'));
        self::assertNull($request->boolean('missing'));
        self::assertNull($request->decimal('missing'));
        self::assertNull($request->enum('missing', RequestTypedInputSlot::class));
        self::assertNull($request->paramInteger('missing'));
        self::assertNull($request->queryInteger('missing'));
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
