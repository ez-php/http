<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Interface HeaderSenderInterface
 *
 * Abstracts the PHP header-sending functions (header() and http_response_code())
 * to allow ResponseEmitter to be tested without an HTTP context.
 *
 * @package EzPhp\Http
 */
interface HeaderSenderInterface
{
    /**
     * Send the HTTP response status code.
     *
     * @param int $code
     *
     * @return void
     */
    public function sendStatus(int $code): void;

    /**
     * Send an HTTP response header.
     *
     * @param string $name
     * @param string $value
     *
     * @return void
     */
    public function sendHeader(string $name, string $value): void;
}
