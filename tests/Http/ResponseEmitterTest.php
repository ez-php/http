<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\HeaderSenderInterface;
use EzPhp\Http\NativeHeaderSender;
use EzPhp\Http\Response;
use EzPhp\Http\ResponseEmitter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

/**
 * Class ResponseEmitterTest
 *
 * @package Tests\Http
 */
#[CoversClass(ResponseEmitter::class)]
#[CoversClass(NativeHeaderSender::class)]
#[UsesClass(Response::class)]
final class ResponseEmitterTest extends TestCase
{
    /**
     * @return void
     */
    public function test_emit_sends_status_code(): void
    {
        $sender = new SpyHeaderSender();
        $emitter = new ResponseEmitter($sender);

        ob_start();
        $emitter->emit(new Response('', 201));
        ob_end_clean();

        $this->assertSame(201, $sender->status);
    }

    /**
     * @return void
     */
    public function test_emit_sends_headers(): void
    {
        $sender = new SpyHeaderSender();
        $emitter = new ResponseEmitter($sender);
        $response = (new Response('', 200))->withHeader('Content-Type', 'application/json');

        ob_start();
        $emitter->emit($response);
        ob_end_clean();

        $this->assertArrayHasKey('Content-Type', $sender->headers);
        $this->assertSame('application/json', $sender->headers['Content-Type']);
    }

    /**
     * @return void
     */
    public function test_emit_outputs_body(): void
    {
        $sender = new SpyHeaderSender();
        $emitter = new ResponseEmitter($sender);

        ob_start();
        $emitter->emit(new Response('Hello World'));
        $output = ob_get_clean();

        $this->assertSame('Hello World', $output);
    }

    /**
     * @return void
     */
    public function test_emit_sends_multiple_headers(): void
    {
        $sender = new SpyHeaderSender();
        $emitter = new ResponseEmitter($sender);
        $response = (new Response('', 200))
            ->withHeader('Content-Type', 'text/html')
            ->withHeader('X-Custom', 'value');

        ob_start();
        $emitter->emit($response);
        ob_end_clean();

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
}
