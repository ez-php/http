<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Interface ResponseInterface
 *
 * Contract for HTTP response objects. Implemented by Response.
 * Used by MiddlewareInterface and ExceptionHandlerInterface (in ez-php/contracts)
 * so that middleware and exception handlers can be typed against a response
 * contract without ez-php/contracts depending on the concrete Response class.
 *
 * @package EzPhp\Http
 */
interface ResponseInterface
{
    /**
     * Return the HTTP status code.
     *
     * @return int
     */
    public function status(): int;

    /**
     * Return the response body.
     *
     * @return string
     */
    public function body(): string;

    /**
     * Return all set headers, keyed by header name.
     *
     * @return array<string, string>
     */
    public function headers(): array;
}
