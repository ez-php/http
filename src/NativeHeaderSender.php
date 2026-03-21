<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Class NativeHeaderSender
 *
 * Production implementation of HeaderSenderInterface using PHP's native
 * http_response_code() and header() functions.
 *
 * @package EzPhp\Http
 */
final class NativeHeaderSender implements HeaderSenderInterface
{
    /**
     * @param int $code
     *
     * @return void
     */
    public function sendStatus(int $code): void
    {
        http_response_code($code);
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return void
     */
    public function sendHeader(string $name, string $value): void
    {
        header("$name: $value");
    }
}
