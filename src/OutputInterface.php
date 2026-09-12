<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Interface OutputInterface
 *
 * Abstracts body output (echo, flushing, client-connection checks) so that
 * ResponseEmitter can be tested without an HTTP context — the body-side
 * counterpart of HeaderSenderInterface.
 *
 * @package EzPhp\Http
 */
interface OutputInterface
{
    /**
     * Write a chunk and flush it to the client immediately.
     *
     * @param string $chunk
     *
     * @return void
     */
    public function write(string $chunk): void;

    /**
     * Whether the client is still connected.
     *
     * @return bool
     */
    public function isClientConnected(): bool;

    /**
     * Keep the script running after the client disconnects, so the emitter
     * can detect the disconnect and terminate() still runs.
     *
     * @return void
     */
    public function ignoreUserAbort(): void;
}
