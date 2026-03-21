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

        $files = self::extractFiles($_FILES);

        return new Request(
            method: $method,
            uri: $uri,
            query: $query,
            body: $body,
            headers: $headers,
            cookies: $cookies,
            server: $server,
            rawBody: $rawBody,
            files: $files,
        );
    }

    /**
     * Build an UploadedFile instance for each field in $_FILES.
     *
     * Multi-file inputs (where each element of the $_FILES entry is itself an
     * array) are intentionally skipped — only single-file-per-field uploads are
     * supported. Files that were not submitted (UPLOAD_ERR_NO_FILE) are excluded.
     *
     * @param array<string, mixed> $filesGlobal
     *
     * @return array<string, UploadedFile>
     */
    private static function extractFiles(array $filesGlobal): array
    {
        $files = [];

        foreach ($filesGlobal as $key => $info) {
            if (!is_array($info)) {
                continue;
            }

            $error = isset($info['error']) && is_int($info['error'])
                ? $info['error']
                : UPLOAD_ERR_NO_FILE;

            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $files[$key] = new UploadedFile(
                originalName: isset($info['name']) && is_string($info['name']) ? $info['name'] : '',
                mimeType: isset($info['type']) && is_string($info['type']) ? $info['type'] : '',
                size: isset($info['size']) && is_int($info['size']) ? $info['size'] : 0,
                tmpName: isset($info['tmp_name']) && is_string($info['tmp_name']) ? $info['tmp_name'] : '',
                error: $error,
            );
        }

        return $files;
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
