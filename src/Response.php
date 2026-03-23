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

    /**
     * Create a JSON response with the appropriate Content-Type header.
     *
     * @param mixed $data   Data to encode as JSON.
     * @param int   $status HTTP status code (default: 200).
     *
     * @return static
     * @throws \JsonException When $data cannot be encoded.
     */
    public static function json(mixed $data, int $status = 200): static
    {
        $body = json_encode($data, JSON_THROW_ON_ERROR);
        return (new static($body, $status))->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create a redirect response.
     *
     * @param string $url    Target URL.
     * @param int    $status HTTP redirect status code (default: 302).
     *
     * @return static
     */
    public static function redirect(string $url, int $status = 302): static
    {
        return (new static('', $status))->withHeader('Location', $url);
    }

    /**
     * Create an HTML response with the appropriate Content-Type header.
     *
     * @param string $body   HTML body.
     * @param int    $status HTTP status code (default: 200).
     *
     * @return static
     */
    public static function html(string $body, int $status = 200): static
    {
        return (new static($body, $status))->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Create a 204 No Content response.
     *
     * @return static
     */
    public static function noContent(): static
    {
        return new static('', 204);
    }
}
