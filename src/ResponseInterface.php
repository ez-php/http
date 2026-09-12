<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Interface ResponseInterface
 *
 * The contract every response travelling through the HTTP pipeline satisfies.
 * Used by MiddlewareInterface and ExceptionHandlerInterface (in ez-php/contracts)
 * and by the framework's middleware pipeline and emitter.
 *
 * The contract is body-neutral: a response writes its own body through
 * writeBody(), so a string body (Response) and a chunked body
 * (StreamedResponse) are emitted the same way. Code that needs the body as a
 * string must check `instanceof Response` first.
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
     * Return all set headers, keyed by header name.
     *
     * @return array<string, string>
     */
    public function headers(): array;

    /**
     * Return a clone with the given header set.
     *
     * Implementations must reject control characters via HeaderValidator.
     *
     * @param string $name
     * @param string $value
     *
     * @throws \InvalidArgumentException When $name or $value contains a CR/LF or other control character.
     *
     * @return static
     */
    public function withHeader(string $name, string $value): static;

    /**
     * Return all queued cookies.
     *
     * @return list<Cookie>
     */
    public function cookies(): array;

    /**
     * Return a clone with the given cookie queued.
     *
     * @param string $name
     * @param string $value
     * @param int    $ttl
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httpOnly
     *
     * @return static
     */
    public function withCookie(
        string $name,
        string $value,
        int $ttl = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = false,
    ): static;

    /**
     * Write the response body by calling $write once per chunk.
     *
     * @param \Closure(string): void $write
     *
     * @return void
     */
    public function writeBody(\Closure $write): void;
}
