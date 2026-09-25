<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\ClientDisconnectedException;
use EzPhp\Http\Cookie;
use EzPhp\Http\HeaderValidator;
use EzPhp\Http\StreamedResponse;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use Tests\TestCase;

/**
 * Class StreamedResponseTest
 *
 * @package Tests\Http
 */
#[CoversClass(StreamedResponse::class)]
#[UsesClass(HeaderValidator::class)]
#[UsesClass(Cookie::class)]
final class StreamedResponseTest extends TestCase
{
    /**
     * Collect everything a response writes.
     *
     * @param StreamedResponse $response
     *
     * @return list<string>
     *
     * @phpstan-impure
     */
    private function collect(StreamedResponse $response): array
    {
        $chunks = [];
        $response->writeBody(function (string $chunk) use (&$chunks): void {
            $chunks[] = $chunk;
        });

        return $chunks;
    }

    /**
     * @return void
     */
    public function test_writes_chunks_in_order(): void
    {
        $response = new StreamedResponse(fn (): iterable => ['a', 'b', 'c']);

        $this->assertSame(['a', 'b', 'c'], $this->collect($response));
    }

    /**
     * @return void
     */
    public function test_factory_makes_the_body_readable_twice(): void
    {
        $response = new StreamedResponse(function (): \Generator {
            yield 'x';
            yield 'y';
        });

        $firstRun = $this->collect($response);
        $secondRun = $this->collect($response);

        $this->assertSame([['x', 'y'], ['x', 'y']], [$firstRun, $secondRun]);
    }

    /**
     * @return void
     */
    public function test_status_and_headers(): void
    {
        $response = new StreamedResponse(fn (): iterable => [], 206, ['X-A' => '1']);

        $this->assertSame(206, $response->status());
        $this->assertSame(['X-A' => '1'], $response->headers());
    }

    /**
     * @return void
     */
    public function test_with_header_returns_a_clone(): void
    {
        $original = new StreamedResponse(fn (): iterable => []);
        $changed = $original->withHeader('X-B', '2');

        $this->assertSame([], $original->headers());
        $this->assertSame(['X-B' => '2'], $changed->headers());
    }

    /**
     * @return void
     */
    public function test_with_header_rejects_crlf(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new StreamedResponse(fn (): iterable => []))->withHeader('X-B', "a\r\nSet-Cookie: x=y");
    }

    /**
     * @return void
     */
    public function test_constructor_headers_are_validated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StreamedResponse(fn (): iterable => [], 200, ["X-Bad\n" => 'v']);
    }

    /**
     * @return void
     */
    public function test_with_cookie_queues_a_cookie_on_a_clone(): void
    {
        $original = new StreamedResponse(fn (): iterable => []);
        $changed = $original->withCookie('session', 'abc');

        $this->assertSame([], $original->cookies());
        $this->assertCount(1, $changed->cookies());
    }

    /**
     * @return void
     */
    public function test_on_error_frames_are_written_then_the_original_exception_is_rethrown(): void
    {
        $failure = new RuntimeException('boom');
        $chunks = [];

        $response = new StreamedResponse(
            function () use ($failure): \Generator {
                yield 'first';
                throw $failure;
            },
            onError: fn (\Throwable $e): iterable => ['error-frame'],
        );

        try {
            $response->writeBody(function (string $chunk) use (&$chunks): void {
                $chunks[] = $chunk;
            });
            $this->fail('Expected the stream exception to be rethrown.');
        } catch (RuntimeException $caught) {
            $this->assertSame($failure, $caught);
        }

        $this->assertSame(['first', 'error-frame'], $chunks);
    }

    /**
     * @return void
     */
    public function test_a_throwing_on_error_does_not_mask_the_original_exception(): void
    {
        $failure = new RuntimeException('original');

        $response = new StreamedResponse(
            function () use ($failure): \Generator {
                yield 'first';
                throw $failure;
            },
            onError: function (\Throwable $e): iterable {
                throw new RuntimeException('secondary');
            },
        );

        try {
            $response->writeBody(function (string $chunk): void {
            });
            $this->fail('Expected the stream exception to be rethrown.');
        } catch (RuntimeException $caught) {
            $this->assertSame($failure, $caught);
        }
    }

    /**
     * @return void
     */
    public function test_client_disconnect_writes_no_error_frames(): void
    {
        $chunks = [];
        $response = new StreamedResponse(
            fn (): iterable => ['a', 'b', 'c'],
            onError: fn (\Throwable $e): iterable => ['error-frame'],
        );

        try {
            $response->writeBody(function (string $chunk) use (&$chunks): void {
                $chunks[] = $chunk;
                if ($chunk === 'b') {
                    throw new ClientDisconnectedException();
                }
            });
            $this->fail('Expected ClientDisconnectedException to be rethrown.');
        } catch (ClientDisconnectedException) {
            // expected
        }

        $this->assertSame(['a', 'b'], $chunks);
    }

    /**
     * @return void
     */
    public function test_download_streams_the_resource_in_chunks_and_sets_headers(): void
    {
        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);
        fwrite($stream, 'abcdefghij');
        rewind($stream);

        $response = StreamedResponse::download($stream, 'report.csv', 10, 'text/csv', 4);

        $this->assertSame('text/csv', $response->headers()['Content-Type']);
        $this->assertSame('10', $response->headers()['Content-Length']);
        $this->assertSame(
            'attachment; filename="report.csv"; filename*=UTF-8\'\'report.csv',
            $response->headers()['Content-Disposition'],
        );
        $this->assertSame(['abcd', 'efgh', 'ij'], $this->collect($response));
    }

    /**
     * @return void
     */
    public function test_download_omits_content_length_when_size_is_unknown(): void
    {
        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);

        $response = StreamedResponse::download($stream, 'a.bin');

        $this->assertArrayNotHasKey('Content-Length', $response->headers());
        $this->assertSame('application/octet-stream', $response->headers()['Content-Type']);
    }

    /**
     * @return void
     */
    public function test_download_encodes_non_ascii_filenames(): void
    {
        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);

        $response = StreamedResponse::download($stream, 'Übersicht März.pdf');

        $this->assertSame(
            'attachment; filename="bersicht Mrz.pdf"; filename*=UTF-8\'\'%C3%9Cbersicht%20M%C3%A4rz.pdf',
            $response->headers()['Content-Disposition'],
        );
    }

    /**
     * @return void
     */
    public function test_download_filename_cannot_inject_headers_or_break_quoting(): void
    {
        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);

        $response = StreamedResponse::download($stream, "evil\"\\\r\nSet-Cookie: x=y.txt");

        $this->assertSame(
            'attachment; filename="evilSet-Cookie: x=y.txt"; filename*=UTF-8\'\'evil%22%5C%0D%0ASet-Cookie%3A%20x%3Dy.txt',
            $response->headers()['Content-Disposition'],
        );
    }

    /**
     * @return void
     */
    public function test_download_closes_the_resource_after_streaming(): void
    {
        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);
        fwrite($stream, 'data');
        rewind($stream);

        $this->collect(StreamedResponse::download($stream, 'a.txt'));

        $this->assertFalse(is_resource($stream));
    }

    /**
     * @return void
     */
    public function test_download_closes_the_resource_on_client_disconnect(): void
    {
        $stream = fopen('php://memory', 'r+');
        self::assertIsResource($stream);
        fwrite($stream, 'abcdefgh');
        rewind($stream);

        $response = StreamedResponse::download($stream, 'a.txt', chunkSize: 2);

        try {
            $response->writeBody(function (string $chunk): void {
                throw new ClientDisconnectedException();
            });
        } catch (ClientDisconnectedException) {
            // expected
        }

        $this->assertFalse(is_resource($stream));
    }

    /**
     * @return void
     */
    public function test_download_rejects_a_non_resource_immediately(): void
    {
        $this->expectException(InvalidArgumentException::class);

        StreamedResponse::download('not a resource', 'a.txt');
    }
}
