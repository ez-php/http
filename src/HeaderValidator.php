<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Class HeaderValidator
 *
 * Rejects header names and values containing CR, LF, or other control
 * characters, so a value built from untrusted input cannot inject additional
 * headers into the response (header/response splitting).
 *
 * PHP's native `header()` already blocks embedded CR/LF, but that protection
 * lives outside the response objects — a `HeaderSenderInterface` that builds a
 * raw response string (e.g. for a non-native SAPI) would not get it for free.
 * Every `ResponseInterface` implementation calls this from `withHeader()`, so
 * the guarantee is part of the contract rather than of one class.
 *
 * @internal
 * @package EzPhp\Http
 */
final class HeaderValidator
{
    /**
     * @param string $name
     * @param string $value
     *
     * @throws \InvalidArgumentException When $name or $value contains a control character.
     *
     * @return void
     */
    public static function assertValid(string $name, string $value): void
    {
        self::assertNoControlCharacters($name, 'header name');
        self::assertNoControlCharacters($value, 'header value');
    }

    /**
     * @param string $value
     * @param string $label
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    private static function assertNoControlCharacters(string $value, string $label): void
    {
        if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw new \InvalidArgumentException("Invalid {$label}: contains a control character.");
        }
    }
}
