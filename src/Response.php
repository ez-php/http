<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Class Response
 *
 * @package EzPhp\Http
 */
final class Response
{
    /**
     * @var array<string, string>
     */
    private array $headers = [];

    /**
     * @var list<Cookie>
     */
    private array $cookies = [];

    /**
     * Response Constructor
     *
     * @param string $body
     * @param int    $status
     */
    public function __construct(
        private string $body = '',
        private int $status = 200
    ) {
    }

    /**
     * @return int
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return Response
     */
    public function withHeader(string $name, string $value): Response
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Return a clone of this response with the given cookie queued.
     *
     * Multiple cookies can be attached by chaining calls. Each call adds one
     * cookie; existing cookies are preserved.
     *
     * @param string $name     Cookie name.
     * @param string $value    Cookie value.
     * @param int    $ttl      Max-Age in seconds. 0 = session cookie.
     * @param string $path     Path scope (default '/').
     * @param string $domain   Domain scope (empty = omit attribute).
     * @param bool   $secure   Restrict to HTTPS.
     * @param bool   $httpOnly Prevent JavaScript access.
     *
     * @return Response
     */
    public function withCookie(
        string $name,
        string $value,
        int $ttl = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = false,
    ): Response {
        $clone = clone $this;
        $clone->cookies[] = new Cookie($name, $value, $ttl, $path, $domain, $secure, $httpOnly);
        return $clone;
    }

    /**
     * Return all queued cookies.
     *
     * @return list<Cookie>
     */
    public function cookies(): array
    {
        return $this->cookies;
    }
}
