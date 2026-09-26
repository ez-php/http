<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Class Headers
 *
 * Case-insensitive operations on a response header map (`name => value`).
 *
 * HTTP header names are case-insensitive (RFC 9110 §5.1), but response headers keep
 * the caller's spelling for output. These helpers make sure one logical header never
 * appears twice under different casings and can be looked up in any casing. Request
 * headers need none of this — `Request` lowercases its keys once at construction.
 *
 * @package EzPhp\Http
 */
final class Headers
{
    /**
     * Return $headers with $name set to $value, replacing an existing header whose name
     * differs only in case. The new spelling wins.
     *
     * @param array<string, string> $headers
     * @param string                $name
     * @param string                $value
     *
     * @return array<string, string>
     */
    public static function set(array $headers, string $name, string $value): array
    {
        $lower = strtolower($name);

        foreach (array_keys($headers) as $existing) {
            if (strtolower($existing) === $lower) {
                unset($headers[$existing]);
            }
        }

        $headers[$name] = $value;

        return $headers;
    }

    /**
     * Look up a header value by name, ignoring case.
     *
     * @param array<string, string> $headers
     * @param string                $name
     *
     * @return string|null Null when the header is not present.
     */
    public static function get(array $headers, string $name): ?string
    {
        $lower = strtolower($name);

        foreach ($headers as $existing => $value) {
            if (strtolower($existing) === $lower) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Collapse headers whose names differ only in case; the last occurrence wins.
     *
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    public static function normalize(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            $normalized = self::set($normalized, $name, $value);
        }

        return $normalized;
    }
}
