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

    /**
     * Send a Set-Cookie header without replacing previously sent cookies.
     *
     * The implementation must send `Set-Cookie: $headerValue` with replace=false
     * so that multiple cookies can be emitted in a single response.
     *
     * @param string $headerValue Serialized cookie string (from Cookie::toHeaderValue()).
     *
     * @return void
     */
    public function sendCookie(string $headerValue): void;
}
