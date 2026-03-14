<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Class RequestFactory
 *
 * @package EzPhp\Http
 */
final class RequestFactory
{
    /**
     * @return Request
     */
    public static function createFromGlobals(): Request
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        if (!is_string($method)) {
            $method = 'GET';
        }

        $uri = $_SERVER['REQUEST_URI'] ?? null;
        if (!is_string($uri)) {
            $uri = '/';
        }

        $query = $_GET;
        $body = $_POST;
        $cookies = $_COOKIE;
        $server = $_SERVER;

        $headers = self::extractHeaders($server);

        $rawBody = file_get_contents('php://input') ?: '';

        return new Request(
            method: $method,
            uri: $uri,
            query: $query,
            body: $body,
            headers: $headers,
            cookies: $cookies,
            server: $server,
            rawBody: $rawBody,
        );
    }

    /**
     * @param array<string, mixed> $server
     *
     * @return array<string, mixed>
     */
    private static function extractHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        if (isset($server['CONTENT_TYPE'])) {
            $headers['content-type'] = $server['CONTENT_TYPE'];
        }

        if (isset($server['CONTENT_LENGTH'])) {
            $headers['content-length'] = $server['CONTENT_LENGTH'];
        }

        return $headers;
    }
}
