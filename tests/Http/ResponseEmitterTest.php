<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\ClientDisconnectedException;
use EzPhp\Http\Cookie;
use EzPhp\Http\HeaderSenderInterface;
use EzPhp\Http\HeaderValidator;
use EzPhp\Http\NativeHeaderSender;
use EzPhp\Http\OutputInterface;
use EzPhp\Http\Response;
use EzPhp\Http\ResponseEmitter;
use EzPhp\Http\StreamedResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use Tests\TestCase;

/**
 * Class ResponseEmitterTest
 *
 * @package Tests\Http
 */
#[CoversClass(ResponseEmitter::class)]
#[CoversClass(NativeHeaderSender::class)]
#[UsesClass(Response::class)]
#[UsesClass(Cookie::class)]
#[UsesClass(StreamedResponse::class)]
#[UsesClass(HeaderValidator::class)]
#[UsesClass(ClientDisconnectedException::class)]
final class ResponseEmitterTest extends TestCase
{
    /**
     * @return void
     */
    public function test_emit_sends_status_code(): void
    {
        $sender = new SpyHeaderSender();
        $emitter = new ResponseEmitter($sender, new RecordingOutput());

        $emitter->emit(new Response('', 201));

        $this->assertSame(201, $sender->status);
    }

    /**
     * @return void
     */
    public function test_emit_sends_headers(): void
    {
        $sender = new SpyHeaderSender();
        $emitter = new ResponseEmitter($sender, new RecordingOutput());
        $response = (new Response('', 200))->withHeader('Content-Type', 'application/json');

        $emitter->emit($response);

        $this->assertArrayHasKey('Content-Type', $sender->headers);
        $this->assertSame('application/json', $sender->headers['Content-Type']);
    }

    /**
     * @return void
     */
    public function test_emit_outputs_body(): void
    {
        $output = new RecordingOutput();
        (new ResponseEmitter(new SpyHeaderSender(), $output))->emit(new Response('Hello World'));

        $this->assertSame(['Hello World'], $output->written);
    }

    /**
     * @return void
     */
    public function test_emit_sends_multiple_headers(): void
    {
        $sender = new SpyHeaderSender();
        $emitter = new ResponseEmitter($sender, new RecordingOutput());
        $response = (new Response('', 200))
            ->withHeader('Content-Type', 'text/html')
            ->withHeader('X-Custom', 'value');

        $emitter->emit($response);

        $this->assertArrayHasKey('Content-Type', $sender->headers);
        $this->assertArrayHasKey('X-Custom', $sender->headers);
        $this->assertSame('value', $sender->headers['X-Custom']);
    }

    /**
     * @return void
     */
    public function test_default_constructor_uses_native_sender(): void
    {
        // Verifies that ResponseEmitter can be constructed without arguments.
        // We cannot call emit() in a CLI context, but construction should not throw.
        $emitter = new ResponseEmitter();
        $this->assertInstanceOf(ResponseEmitter::class, $emitter);
    }

    /**
     * @return void
     */
    public function test_emit_sends_cookies_via_send_cookie(): void
    {
        $sender = new SpyHeaderSender();
        $emitter = new ResponseEmitter($sender, new RecordingOutput());
        $response = (new Response())->withCookie('session', 'abc123');

        $emitter->emit($response);

        $this->assertCount(1, $sender->cookies);
        $this->assertStringContainsString('session=abc123', $sender->cookies[0]);
    }

    /**
     * @return void
     */
    public function test_emit_sends_multiple_cookies(): void
    {
        $sender = new SpyHeaderSender();
        $emitter = new ResponseEmitter($sender, new RecordingOutput());
        $response = (new Response())
            ->withCookie('a', '1')
            ->withCookie('b', '2');

        $emitter->emit($response);

        $this->assertCount(2, $sender->cookies);
    }

    /**
     * @return void
     */
    public function test_string_response_is_written_once_through_the_output(): void
    {
        $output = new RecordingOutput();
        (new ResponseEmitter(new SpyHeaderSender(), $output))->emit(new Response('Hello'));

        $this->assertSame(['Hello'], $output->written);
        $this->assertTrue($output->ignoredUserAbort);
    }

    /**
     * @return void
     */
    public function test_streamed_response_is_written_chunk_by_chunk_after_headers(): void
    {
        $sender = new SpyHeaderSender();
        $output = new RecordingOutput();
        $response = new StreamedResponse(fn (): iterable => ['a', 'b', 'c'], 200, ['X-Stream' => '1']);

        (new ResponseEmitter($sender, $output))->emit($response);

        $this->assertSame('1', $sender->headers['X-Stream']);
        $this->assertSame(['a', 'b', 'c'], $output->written);
    }

    /**
     * @return void
     */
    public function test_client_disconnect_ends_the_stream_quietly(): void
    {
        $output = new RecordingOutput(disconnectAfter: 2);
        $called = false;
        $response = new StreamedResponse(fn (): iterable => ['a', 'b', 'c', 'd']);

        (new ResponseEmitter(new SpyHeaderSender(), $output))->emit(
            $response,
            function (\Throwable $e) use (&$called): void {
                $called = true;
            },
        );

        $this->assertSame(['a', 'b'], $output->written);
        $this->assertFalse($called, 'A disconnect is not an error and must not be reported.');
    }

    /**
     * @return void
     */
    public function test_stream_error_is_passed_to_the_callback_once(): void
    {
        $failure = new RuntimeException('boom');
        $received = [];
        $response = new StreamedResponse(function () use ($failure): \Generator {
            yield 'a';
            throw $failure;
        });

        (new ResponseEmitter(new SpyHeaderSender(), new RecordingOutput()))->emit(
            $response,
            function (\Throwable $e) use (&$received): void {
                $received[] = $e;
            },
        );

        $this->assertSame([$failure], $received);
    }

    /**
     * @return void
     */
    public function test_stream_error_is_rethrown_without_a_callback(): void
    {
        $response = new StreamedResponse(function (): \Generator {
            yield 'a';
            throw new RuntimeException('boom');
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('boom');

        (new ResponseEmitter(new SpyHeaderSender(), new RecordingOutput()))->emit($response);
    }
}

/**
 * Class SpyHeaderSender
 *
 * Test spy that records sendStatus() and sendHeader() calls.
 *
 * @package Tests\Http
 */
final class SpyHeaderSender implements HeaderSenderInterface
{
    public int $status = 0;

    /** @var array<string, string> */
    public array $headers = [];

    /** @var list<string> */
    public array $cookies = [];

    /**
     * @param int $code
     *
     * @return void
     */
    public function sendStatus(int $code): void
    {
        $this->status = $code;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return void
     */
    public function sendHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * @param string $headerValue
     *
     * @return void
     */
    public function sendCookie(string $headerValue): void
    {
        $this->cookies[] = $headerValue;
    }
}

/**
 * Class RecordingOutput
 *
 * Test OutputInterface that records writes and can simulate a client that
 * disconnects after a given number of writes.
 *
 * @package Tests\Http
 */
final class RecordingOutput implements OutputInterface
{
    /** @var list<string> */
    public array $written = [];

    public bool $ignoredUserAbort = false;

    /**
     * @param int|null $disconnectAfter Report the client as gone once this many chunks were written.
     */
    public function __construct(private readonly ?int $disconnectAfter = null)
    {
    }

    /**
     * @param string $chunk
     *
     * @return void
     */
    public function write(string $chunk): void
    {
        $this->written[] = $chunk;
    }

    /**
     * @return bool
     */
    public function isClientConnected(): bool
    {
        return $this->disconnectAfter === null || count($this->written) < $this->disconnectAfter;
    }

    /**
     * @return void
     */
    public function ignoreUserAbort(): void
    {
        $this->ignoredUserAbort = true;
    }
}
