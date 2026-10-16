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
     * Normalized SameSite attribute (`Lax`, `Strict`, `None`) or empty when omitted.
     */
    private string $sameSite;

    /**
     * Cookie Constructor
     *
     * @param string $name     Cookie name.
     * @param string $value    Cookie value (will be URL-encoded in the header).
     * @param int    $ttl      Max-Age in seconds. 0 means a session cookie (no Max-Age sent);
     *                          a negative value deletes the cookie (`Max-Age=0`).
     * @param string $path     Path scope. Defaults to '/'.
     * @param string $domain   Domain scope. Empty string omits the attribute.
     * @param bool   $secure   Restrict to HTTPS connections.
     * @param bool   $httpOnly Prevent JavaScript access.
     * @param string $sameSite SameSite attribute: `Lax`, `Strict` or `None` (case-insensitive);
     *                         empty omits it (browsers then default to Lax). `None` requires $secure.
     *
     * @throws \InvalidArgumentException When $sameSite is not one of the allowed values,
     *                                   or is `None` on a non-secure cookie.
     */
    public function __construct(
        private string $name,
        private string $value,
        private int $ttl = 0,
        private string $path = '/',
        private string $domain = '',
        private bool $secure = false,
        private bool $httpOnly = false,
        string $sameSite = '',
    ) {
        $this->sameSite = self::normalizeSameSite($sameSite, $secure);
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
     * SameSite attribute (`Lax`, `Strict`, `None`), or an empty string when omitted.
     *
     * @return string
     */
    public function sameSite(): string
    {
        return $this->sameSite;
    }

    /**
     * Serialize this cookie to a Set-Cookie header value string.
     *
     * Name and value are URL-encoded. Max-Age is emitted only when ttl > 0.
     * Path and Domain are validated via HeaderValidator (rejecting embedded
     * control characters) before being emitted, matching the same guarantee
     * withHeader() provides for arbitrary response headers.
     *
     * @throws \InvalidArgumentException When path or domain contains a control character.
     *
     * @return string
     */
    public function toHeaderValue(): string
    {
        $parts = [urlencode($this->name) . '=' . urlencode($this->value)];

        if ($this->ttl > 0) {
            $parts[] = 'Max-Age=' . $this->ttl;
        } elseif ($this->ttl < 0) {
            // Delete: the browser drops a cookie whose Max-Age is zero or less.
            $parts[] = 'Max-Age=0';
        }

        if ($this->path !== '') {
            HeaderValidator::assertValid('Path', $this->path);
            $parts[] = 'Path=' . $this->path;
        }

        if ($this->domain !== '') {
            HeaderValidator::assertValid('Domain', $this->domain);
            $parts[] = 'Domain=' . $this->domain;
        }

        if ($this->secure) {
            $parts[] = 'Secure';
        }

        if ($this->httpOnly) {
            $parts[] = 'HttpOnly';
        }

        if ($this->sameSite !== '') {
            $parts[] = 'SameSite=' . $this->sameSite;
        }

        return implode('; ', $parts);
    }

    /**
     * @param string $sameSite
     * @param bool   $secure
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    private static function normalizeSameSite(string $sameSite, bool $secure): string
    {
        if ($sameSite === '') {
            return '';
        }

        $normalized = match (strtolower($sameSite)) {
            'lax' => 'Lax',
            'strict' => 'Strict',
            'none' => 'None',
            default => throw new \InvalidArgumentException(
                "Invalid SameSite value '{$sameSite}': expected Lax, Strict or None."
            ),
        };

        // Browsers reject SameSite=None without Secure.
        if ($normalized === 'None' && !$secure) {
            throw new \InvalidArgumentException('SameSite=None requires a secure cookie.');
        }

        return $normalized;
    }
}
