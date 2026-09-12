<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\HeaderValidator;
use EzPhp\Http\Sse\SseEvent;
use EzPhp\Http\StreamedResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use Tests\TestCase;

/**
 * Class StreamedResponseSseTest
 *
 * @package Tests\Http
 */
#[CoversClass(StreamedResponse::class)]
#[UsesClass(SseEvent::class)]
#[UsesClass(HeaderValidator::class)]
final class StreamedResponseSseTest extends TestCase
{
    /**
     * @return void
     */
    public function test_sse_sets_event_stream_headers(): void
    {
        $response = StreamedResponse::sse(fn (): iterable => []);

        $this->assertSame(
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ],
            $response->headers(),
        );
    }

    /**
     * @return void
     */
    public function test_sse_writes_one_frame_per_event(): void
    {
        $response = StreamedResponse::sse(fn (): iterable => [
            new SseEvent('one', 'token'),
            new SseEvent('two', 'token'),
        ]);

        $chunks = [];
        $response->writeBody(function (string $chunk) use (&$chunks): void {
            $chunks[] = $chunk;
        });

        $this->assertSame(["event: token\ndata: one\n\n", "event: token\ndata: two\n\n"], $chunks);
    }

    /**
     * @return void
     */
    public function test_sse_error_frame_is_generic_and_does_not_leak_the_exception_message(): void
    {
        $response = StreamedResponse::sse(function (): \Generator {
            yield new SseEvent('ok');
            throw new RuntimeException('SQLSTATE[HY000]: secret connection string');
        });

        $chunks = [];

        try {
            $response->writeBody(function (string $chunk) use (&$chunks): void {
                $chunks[] = $chunk;
            });
            $this->fail('Expected the stream exception to be rethrown.');
        } catch (RuntimeException) {
            // expected — reported by the framework, not by the response
        }

        $this->assertSame(
            ["data: ok\n\n", "event: error\ndata: " . StreamedResponse::SSE_ERROR_PAYLOAD . "\n\n"],
            $chunks,
        );
        $this->assertStringNotContainsString('secret', implode('', $chunks));
    }
}
