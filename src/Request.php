<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Class Request
 *
 * @package EzPhp\Http
 */
final readonly class Request implements RequestInterface
{
    /**
     * Header values keyed by lower-cased header name.
     *
     * @var array<string, mixed>
     */
    private array $headers;

    /**
     * Request Constructor
     *
     * @param string $method
     * @param string $uri
     * @param array<string, mixed>        $query
     * @param array<string, mixed>        $body
     * @param array<string, mixed>        $headers Header names are lower-cased here, so every reader
     *                                             (header(), contentType(), accepts(), ip()) is case-insensitive
     *                                             however the request was built.
     * @param array<string, mixed>        $cookies
     * @param array<string, mixed>        $server
     * @param string                      $rawBody
     * @param array<string, mixed>        $params
     * @param array<string, UploadedFile> $files
     */
    public function __construct(
        private string $method,
        private string $uri,
        private array $query = [],
        private array $body = [],
        array $headers = [],
        private array $cookies = [],
        private array $server = [],
        private string $rawBody = '',
        private array $params = [],
        private array $files = [],
    ) {
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    /**
     * @return string
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Return a query-string parameter by key, or $default when the key is absent.
     *
     * Missing key convention: returns $default (null by default). Never throws.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Return a parsed body parameter by key, or $default when the key is absent.
     *
     * Missing key convention: returns $default (null by default). Never throws.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->parsedBody()[$key] ?? $default;
    }

    /**
     * Read a body value as an integer, strictly.
     *
     * Returns the int for an int, or for a string of plain decimal digits with an
     * optional minus sign (no leading zeros, whitespace, `+`, fraction or exponent)
     * that fits into a PHP int. Anything else — `"12abc"`, `12.0`, `true` — is null,
     * never a best-effort cast. Missing keys are null too.
     *
     * @param string $key
     *
     * @return int|null
     */
    public function integer(string $key): ?int
    {
        $value = $this->input($key);

        if (is_int($value)) {
            return $value;
        }

        if (!is_string($value) || preg_match('/^-?(0|[1-9][0-9]*)$/', $value) !== 1) {
            return null;
        }

        $int = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($int) ? $int : null; // false on overflow
    }

    /**
     * Read a body value as a boolean, strictly.
     *
     * Accepts `true`/`false`, the strings `"true"`/`"false"`/`"1"`/`"0"` (form bodies
     * send strings) and the ints `1`/`0`. Anything else — `"yes"`, `"on"`, `2` — is null.
     *
     * @param string $key
     *
     * @return bool|null
     */
    public function boolean(string $key): ?bool
    {
        return match ($this->input($key)) {
            true, 'true', '1', 1 => true,
            false, 'false', '0', 0 => false,
            default => null,
        };
    }

    /**
     * Read a body value as a decimal number, returned as a normalized string.
     *
     * Accepts an int, a float, or a string of the form `-?digits(.digits)?` (no
     * exponent, comma or leading dot). With `$scale`, a value with more fraction
     * digits than `$scale` is null — it is never rounded — and the result is padded
     * to exactly `$scale` digits (`"12.5"` → `"12.50"`). A string is returned so
     * money-like values never pass through float rounding.
     *
     * @param string   $key
     * @param int|null $scale Maximum (and output) number of fraction digits; null keeps them as given.
     *
     * @return string|null
     */
    public function decimal(string $key, ?int $scale = null): ?string
    {
        $value = $this->input($key);

        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        if (!is_string($value) || preg_match('/^(-?[0-9]+)(?:\.([0-9]+))?$/', $value, $m) !== 1) {
            return null;
        }

        $fraction = $m[2] ?? '';

        if ($scale === null) {
            return $value;
        }

        if (strlen($fraction) > $scale) {
            return null;
        }

        return $scale === 0 ? $m[1] : $m[1] . '.' . str_pad($fraction, $scale, '0');
    }

    /**
     * Return all query and body parameters merged into a single array.
     *
     * When Content-Type is application/json and the body array is empty, the
     * raw request body is decoded and merged transparently with query parameters.
     * Body values take precedence over query values on key collision.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->parsedBody());
    }

    /**
     * Determine whether the given key is present in either query or body.
     *
     * Uses array_key_exists so a key with a null value is considered present.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->query) || array_key_exists($key, $this->parsedBody());
    }

    /**
     * Determine whether the given key is present in the query string.
     *
     * Uses array_key_exists so a key with a null value is considered present.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasQuery(string $key): bool
    {
        return array_key_exists($key, $this->query);
    }

    /**
     * Determine whether the given key is present in the request body.
     *
     * Uses array_key_exists so a key with a null value is considered present.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasInput(string $key): bool
    {
        return array_key_exists($key, $this->parsedBody());
    }

    /**
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function header(string $key, mixed $default = null): mixed
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    /**
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * @return string
     */
    public function rawBody(): string
    {
        return $this->rawBody;
    }

    /**
     * Resolve the client IP address.
     *
     * When $trustedProxies is non-empty and REMOTE_ADDR matches one of the
     * trusted entries, the X-Forwarded-For chain is walked from the right
     * (each proxy appends the address it saw), skipping trusted proxies, and
     * the first untrusted hop is returned — everything left of it is
     * client-controlled and may be forged. If every hop is trusted, the
     * leftmost entry is returned. Falls back to REMOTE_ADDR when the XFF header
     * is absent or the first untrusted hop is not a valid IP.
     * Returns an empty string when REMOTE_ADDR is not set.
     *
     * @param list<string> $trustedProxies IP addresses of trusted reverse proxies.
     *
     * @return string
     */
    public function ip(array $trustedProxies = []): string
    {
        $remoteAddr = $this->server['REMOTE_ADDR'] ?? '';
        $remoteAddr = is_string($remoteAddr) ? $remoteAddr : '';

        if ($trustedProxies !== [] && in_array($remoteAddr, $trustedProxies, true)) {
            $xff = $this->headers['x-forwarded-for'] ?? null;

            if (is_string($xff) && $xff !== '') {
                $hops = array_map(trim(...), explode(',', $xff));
                $candidate = $hops[0];

                for ($i = count($hops) - 1; $i >= 0; $i--) {
                    if (!in_array($hops[$i], $trustedProxies, true)) {
                        $candidate = $hops[$i];
                        break;
                    }
                }

                if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                    return $candidate;
                }
            }
        }

        return $remoteAddr;
    }

    /**
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Return the uploaded file for the given form field name, or null if absent.
     *
     * @param string $key The form field name corresponding to the file input.
     *
     * @return UploadedFile|null
     */
    public function file(string $key): ?UploadedFile
    {
        return $this->files[$key] ?? null;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return self
     */
    public function withParams(array $params): self
    {
        return new self(
            method: $this->method,
            uri: $this->uri,
            query: $this->query,
            body: $this->body,
            headers: $this->headers,
            cookies: $this->cookies,
            server: $this->server,
            rawBody: $this->rawBody,
            params: $params,
            files: $this->files,
        );
    }

    /**
     * @param string $method
     *
     * @return self
     */
    public function withMethod(string $method): self
    {
        return new self(
            method: $method,
            uri: $this->uri,
            query: $this->query,
            body: $this->body,
            headers: $this->headers,
            cookies: $this->cookies,
            server: $this->server,
            rawBody: $this->rawBody,
            params: $this->params,
            files: $this->files,
        );
    }

    // ── JSON body parsing ─────────────────────────────────────────────────────

    /**
     * Return the parsed request body.
     *
     * When the explicit body array is non-empty it is returned as-is (form-
     * encoded POST or manually constructed requests). When it is empty and the
     * request carries a JSON Content-Type, the raw body is decoded and returned
     * instead. Invalid JSON yields an empty array.
     *
     * @return array<string, mixed>
     */
    private function parsedBody(): array
    {
        if ($this->body !== []) {
            return $this->body;
        }

        if ($this->rawBody === '' || !$this->isJson()) {
            return $this->body;
        }

        try {
            $decoded = json_decode($this->rawBody, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (\JsonException) {
            return [];
        }
    }

    // ── Content negotiation ───────────────────────────────────────────────────

    /**
     * Return the raw Content-Type header value, or null if absent.
     *
     * @return string|null
     */
    public function contentType(): ?string
    {
        $value = $this->headers['content-type'] ?? null;
        return is_string($value) ? $value : null;
    }

    /**
     * Determine whether the request body is JSON.
     *
     * Returns true when the Content-Type header contains "application/json".
     *
     * @return bool
     */
    public function isJson(): bool
    {
        $contentType = $this->contentType();
        return $contentType !== null && str_contains($contentType, 'application/json');
    }

    /**
     * Determine whether the client accepts a JSON response.
     *
     * Returns true when the Accept header contains "application/json" or "*\/*".
     *
     * @return bool
     */
    public function acceptsJson(): bool
    {
        return $this->accepts('application/json');
    }

    /**
     * Determine whether the request targets a JSON exchange.
     *
     * Returns true when the request is JSON (Content-Type) or the client
     * accepts JSON (Accept header).
     *
     * @return bool
     */
    public function wantsJson(): bool
    {
        return $this->isJson() || $this->acceptsJson();
    }

    /**
     * Determine whether the client accepts the given content type.
     *
     * Returns true when the Accept header contains the given type or "*\/*".
     *
     * @param string $contentType MIME type to check, e.g. "text/html".
     *
     * @return bool
     */
    public function accepts(string $contentType): bool
    {
        $accept = $this->headers['accept'] ?? null;
        if (!is_string($accept)) {
            return false;
        }

        return str_contains($accept, $contentType) || str_contains($accept, '*/*');
    }
}
