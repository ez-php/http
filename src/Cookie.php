<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Class Cookie
 *
 * Immutable value object representing an outgoing Set-Cookie header.
 * All attributes follow RFC 6265.
 *
 * @package EzPhp\Http
 */
final readonly class Cookie
{
    /**
     * Cookie Constructor
     *
     * @param string $name     Cookie name.
     * @param string $value    Cookie value (will be URL-encoded in the header).
     * @param int    $ttl      Max-Age in seconds. 0 means a session cookie (no Max-Age sent).
     * @param string $path     Path scope. Defaults to '/'.
     * @param string $domain   Domain scope. Empty string omits the attribute.
     * @param bool   $secure   Restrict to HTTPS connections.
     * @param bool   $httpOnly Prevent JavaScript access.
     */
    public function __construct(
        private string $name,
        private string $value,
        private int $ttl = 0,
        private string $path = '/',
        private string $domain = '',
        private bool $secure = false,
        private bool $httpOnly = false,
    ) {
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function ttl(): int
    {
        return $this->ttl;
    }

    /**
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function domain(): string
    {
        return $this->domain;
    }

    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * @return bool
     */
    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * Serialize this cookie to a Set-Cookie header value string.
     *
     * Name and value are URL-encoded. Max-Age is emitted only when ttl > 0.
     * Path is always emitted when non-empty.
     *
     * @return string
     */
    public function toHeaderValue(): string
    {
        $parts = [urlencode($this->name) . '=' . urlencode($this->value)];

        if ($this->ttl > 0) {
            $parts[] = 'Max-Age=' . $this->ttl;
        }

        if ($this->path !== '') {
            $parts[] = 'Path=' . $this->path;
        }

        if ($this->domain !== '') {
            $parts[] = 'Domain=' . $this->domain;
        }

        if ($this->secure) {
            $parts[] = 'Secure';
        }

        if ($this->httpOnly) {
            $parts[] = 'HttpOnly';
        }

        return implode('; ', $parts);
    }
}
