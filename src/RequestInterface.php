<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Interface RequestInterface
 *
 * Contract for HTTP request objects. Implemented by Request.
 * Used by MiddlewareInterface and ExceptionHandlerInterface so that
 * middleware and exception handlers can be used without depending on
 * the concrete Request class.
 *
 * @package EzPhp\Http
 */
interface RequestInterface
{
    /**
     * Return the HTTP method (uppercase), e.g. 'GET', 'POST'.
     *
     * @return string
     */
    public function method(): string;

    /**
     * Return the full request URI including query string.
     *
     * @return string
     */
    public function uri(): string;

    /**
     * Return a query-string parameter by key, or $default when absent.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function query(string $key, mixed $default = null): mixed;

    /**
     * Return a parsed body parameter by key, or $default when absent.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed;

    /**
     * Return all query and body parameters merged.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Determine whether the given key is present in query or body.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Determine whether the given key is present in the query string.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasQuery(string $key): bool;

    /**
     * Determine whether the given key is present in the request body.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasInput(string $key): bool;

    /**
     * Return a header value by key (lowercase), or $default when absent.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function header(string $key, mixed $default = null): mixed;

    /**
     * Return a cookie value by key, or $default when absent.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function cookie(string $key, mixed $default = null): mixed;

    /**
     * Return a server variable by key, or $default when absent.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function server(string $key, mixed $default = null): mixed;

    /**
     * Return the raw php://input body.
     *
     * @return string
     */
    public function rawBody(): string;

    /**
     * Resolve the client IP address.
     *
     * @param list<string> $trustedProxies
     *
     * @return string
     */
    public function ip(array $trustedProxies = []): string;

    /**
     * Return a URL route parameter by key, or $default when absent.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function param(string $key, mixed $default = null): mixed;

    /**
     * Return the uploaded file for the given form field name, or null if absent.
     *
     * @param string $key
     *
     * @return UploadedFile|null
     */
    public function file(string $key): ?UploadedFile;

    /**
     * Return the raw Content-Type header value, or null if absent.
     *
     * @return string|null
     */
    public function contentType(): ?string;

    /**
     * Determine whether the request body is JSON.
     *
     * @return bool
     */
    public function isJson(): bool;

    /**
     * Determine whether the client accepts a JSON response.
     *
     * @return bool
     */
    public function acceptsJson(): bool;

    /**
     * Determine whether the request targets a JSON exchange.
     *
     * @return bool
     */
    public function wantsJson(): bool;

    /**
     * Determine whether the client accepts the given content type.
     *
     * @param string $contentType
     *
     * @return bool
     */
    public function accepts(string $contentType): bool;
}
