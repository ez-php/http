<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Class Request
 *
 * @package EzPhp\Http
 */
final readonly class Request
{
    /**
     * Request Constructor
     *
     * @param string $method
     * @param string $uri
     * @param array<string, mixed>  $query
     * @param array<string, mixed>  $body
     * @param array<string, mixed>  $headers
     * @param array<string, mixed>  $cookies
     * @param array<string, mixed>  $server
     * @param string $rawBody
     * @param array<string, string> $params
     */
    public function __construct(
        private string $method,
        private string $uri,
        private array $query = [],
        private array $body = [],
        private array $headers = [],
        private array $cookies = [],
        private array $server = [],
        private string $rawBody = '',
        private array $params = [],
    ) {
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
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
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
     * @param array<string, string> $params
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
        );
    }
}
