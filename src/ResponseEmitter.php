<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Class ResponseEmitter
 *
 * @package EzPhp\Http
 */
final class ResponseEmitter
{
    /**
     * @param Response $response
     *
     * @return void
     */
    public function emit(Response $response): void
    {
        http_response_code($response->status());

        foreach ($response->headers() as $name => $value) {
            header("$name: $value");
        }

        echo $response->body();
    }
}
