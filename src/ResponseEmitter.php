<?php

declare(strict_types=1);

namespace EzPhp\Http;

use Throwable;

/**
 * Class ResponseEmitter
 *
 * Sends a response to the HTTP client: status, headers and cookies through a
 * HeaderSenderInterface, then the body through an OutputInterface by letting
 * the response write itself (ResponseInterface::writeBody()).
 *
 * The emitter keeps the script alive after a client disconnect
 * (ignore_user_abort) and checks the connection after every chunk. A
 * disconnect ends the body quietly. Any other exception raised while the body
 * is written happens after headers were sent, so it cannot become an error
 * page; it is handed to $onStreamError for reporting, or rethrown when no
 * callback is given.
 *
 * This package has no dependencies, so it cannot know the application's
 * exception handler — the framework passes reporting in as a callback.
 *
 * @package EzPhp\Http
 */
final readonly class ResponseEmitter
{
    /**
     * ResponseEmitter Constructor
     *
     * @param HeaderSenderInterface $headerSender Header-sending strategy.
     * @param OutputInterface       $output       Body output strategy.
     */
    public function __construct(
        private HeaderSenderInterface $headerSender = new NativeHeaderSender(),
        private OutputInterface $output = new NativeOutput(),
    ) {
    }

    /**
     * @param ResponseInterface                $response
     * @param (\Closure(Throwable): void)|null $onStreamError Receives failures raised while the body is written.
     *
     * @throws Throwable When the body fails and no $onStreamError is given.
     *
     * @return void
     */
    public function emit(ResponseInterface $response, ?\Closure $onStreamError = null): void
    {
        $this->headerSender->sendStatus($response->status());

        foreach ($response->headers() as $name => $value) {
            $this->headerSender->sendHeader($name, $value);
        }

        foreach ($response->cookies() as $cookie) {
            $this->headerSender->sendCookie($cookie->toHeaderValue());
        }

        $this->output->ignoreUserAbort();

        try {
            $response->writeBody(function (string $chunk): void {
                $this->output->write($chunk);

                if (!$this->output->isClientConnected()) {
                    throw new ClientDisconnectedException();
                }
            });
        } catch (ClientDisconnectedException) {
            return;
        } catch (Throwable $e) {
            if ($onStreamError === null) {
                throw $e;
            }

            $onStreamError($e);
        }
    }
}
