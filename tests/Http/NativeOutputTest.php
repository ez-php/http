<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\ClientDisconnectedException;
use EzPhp\Http\NativeOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Class NativeOutputTest
 *
 * Only what is observable from the CLI SAPI. write() is deliberately not tested
 * here: it calls ob_flush(), which pushes output past PHPUnit's capture buffer,
 * so any assertion on it would be unreliable. NativeOutput is a thin SAPI
 * adapter like NativeHeaderSender, whose header() calls are untested for the
 * same reason; emitter behaviour is covered through a fake OutputInterface in
 * ResponseEmitterTest.
 *
 * @package Tests\Http
 */
#[CoversClass(NativeOutput::class)]
#[CoversClass(ClientDisconnectedException::class)]
final class NativeOutputTest extends TestCase
{
    /**
     * @return void
     */
    public function test_cli_client_counts_as_connected(): void
    {
        $this->assertTrue((new NativeOutput())->isClientConnected());
    }

    /**
     * @return void
     */
    public function test_client_disconnected_exception_is_a_runtime_exception(): void
    {
        $this->assertInstanceOf(\RuntimeException::class, new ClientDisconnectedException());
    }
}
