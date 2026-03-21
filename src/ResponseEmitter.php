<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Class ResponseEmitter
 *
 * Sends a Response to the HTTP client. The header-sending logic is delegated
 * to a HeaderSenderInterface, which allows testing without a real HTTP context.
 *
 * @package EzPhp\Http
 */
final readonly class ResponseEmitter
{
    /**
     * ResponseEmitter Constructor
     *
     * @param HeaderSenderInterface $headerSender Header-sending strategy. Defaults to NativeHeaderSender.
     */
    public function __construct(
        private HeaderSenderInterface $headerSender = new NativeHeaderSender()
    ) {
    }

    /**
     * @param Response $response
     *
     * @return void
     */
    public function emit(Response $response): void
    {
        $this->headerSender->sendStatus($response->status());

        foreach ($response->headers() as $name => $value) {
            $this->headerSender->sendHeader($name, $value);
        }

        echo $response->body();
    }
}
