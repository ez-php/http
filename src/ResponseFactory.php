<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Class ResponseFactory
 *
 * Static factory for common Response types.
 * Produces pre-configured Response value objects without extending or modifying Response itself.
 *
 * @package EzPhp\Http
 */
final class ResponseFactory
{
    /**
     * Create a JSON response.
     *
     * Encodes $data as JSON and sets Content-Type: application/json.
     *
     * @param mixed $data   Any JSON-serialisable value.
     * @param int   $status HTTP status code (default 200).
     *
     * @return Response
     */
    public static function json(mixed $data, int $status = 200): Response
    {
        $body = json_encode($data, JSON_THROW_ON_ERROR);

        return (new Response($body, $status))
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create an HTML response.
     *
     * Sets Content-Type: text/html; charset=UTF-8.
     *
     * @param string $body   HTML content.
     * @param int    $status HTTP status code (default 200).
     *
     * @return Response
     */
    public static function html(string $body, int $status = 200): Response
    {
        return (new Response($body, $status))
            ->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Create a plain-text response.
     *
     * Sets Content-Type: text/plain; charset=UTF-8.
     *
     * @param string $body   Text content.
     * @param int    $status HTTP status code (default 200).
     *
     * @return Response
     */
    public static function text(string $body, int $status = 200): Response
    {
        return (new Response($body, $status))
            ->withHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * Create a redirect response.
     *
     * Sets the Location header and returns an empty body.
     * Use status 301 for permanent, 302 (default) for temporary redirects.
     *
     * @param string $url    Target URL.
     * @param int    $status HTTP status code (default 302).
     *
     * @return Response
     */
    public static function redirect(string $url, int $status = 302): Response
    {
        return (new Response('', $status))
            ->withHeader('Location', $url);
    }

    /**
     * Create a 204 No Content response.
     *
     * @return Response
     */
    public static function noContent(): Response
    {
        return new Response('', 204);
    }
}
