<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Class NativeOutput
 *
 * Production OutputInterface using echo, ob_flush()/flush() and
 * connection_aborted().
 *
 * @package EzPhp\Http
 */
final class NativeOutput implements OutputInterface
{
    /**
     * @param string $chunk
     *
     * @return void
     */
    public function write(string $chunk): void
    {
        echo $chunk;

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }

    /**
     * @return bool
     */
    public function isClientConnected(): bool
    {
        return connection_aborted() === 0;
    }

    /**
     * @return void
     */
    public function ignoreUserAbort(): void
    {
        ignore_user_abort(true);
    }
}
