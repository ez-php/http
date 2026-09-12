<?php

declare(strict_types=1);

namespace EzPhp\Http;

use EzPhp\Http\Sse\SseEvent;
use Throwable;

/**
 * Class StreamedResponse
 *
 * A response whose body is produced in chunks while it is being sent — for file
 * downloads, Server-Sent Events and other long-running output. It travels
 * through the middleware pipeline like any other ResponseInterface.
 *
 * **Headers are sent before the first chunk.** Any exception thrown inside the
 * chunk factory is therefore a mid-stream failure, even one before the first
 * chunk: it can no longer become an error page. Do authorisation, validation
 * and existence checks in the controller *before* returning this response.
 *
 * **Chunks come from a factory**, not an iterable, so every writeBody() call
 * gets a fresh iterator: tests can read the body, and clones created by the
 * withers never share a half-consumed generator. download() is the exception —
 * a resource can only be read once.
 *
 * @package EzPhp\Http
 */
final class StreamedResponse implements ResponseInterface
{
    /**
     * Payload of the error frame sse() writes when the stream fails. Generic on
     * purpose: the exception message is never sent to the client.
     */
    public const string SSE_ERROR_PAYLOAD = '{"message":"stream error"}';

    /**
     * @var array<string, string>
     */
    private array $headers = [];

    /**
     * @var list<Cookie>
     */
    private array $cookies = [];

    /**
     * StreamedResponse Constructor
     *
     * @param \Closure(): iterable<string>                 $chunks  Factory, invoked on every writeBody() call.
     * @param int                                          $status
     * @param array<string, string>                        $headers
     * @param (\Closure(Throwable): iterable<string>)|null $onError Error frames written before the exception is rethrown.
     *
     * @throws \InvalidArgumentException When a header contains a control character.
     */
    public function __construct(
        private readonly \Closure $chunks,
        private readonly int $status = 200,
        array $headers = [],
        private readonly ?\Closure $onError = null,
    ) {
        foreach ($headers as $name => $value) {
            HeaderValidator::assertValid($name, $value);
        }

        $this->headers = $headers;
    }

    /**
     * Stream a readable resource as a file download.
     *
     * The resource is validated immediately (before any header is sent) and
     * closed after streaming — also on error and on client disconnect.
     *
     * @param mixed       $stream      Readable stream resource, e.g. from StorageInterface::getStream().
     * @param string      $filename    Suggested download filename; sanitised for the header.
     * @param int|null    $size        Content-Length in bytes, or null when unknown.
     * @param string      $contentType
     * @param int<1, max> $chunkSize   Bytes per chunk.
     *
     * @throws \InvalidArgumentException When $stream is not a resource.
     *
     * @return self
     */
    public static function download(
        mixed $stream,
        string $filename,
        ?int $size = null,
        string $contentType = 'application/octet-stream',
        int $chunkSize = 8192,
    ): self {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('StreamedResponse::download() expects a readable stream resource.');
        }

        $headers = [
            'Content-Type' => $contentType,
            'Content-Disposition' => self::contentDisposition($filename),
        ];

        if ($size !== null) {
            $headers['Content-Length'] = (string) $size;
        }

        return new self(
            static function () use ($stream, $chunkSize): \Generator {
                try {
                    while (is_resource($stream) && !feof($stream)) {
                        $chunk = fread($stream, $chunkSize);

                        if ($chunk === false || $chunk === '') {
                            break;
                        }

                        yield $chunk;
                    }
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            },
            200,
            $headers,
        );
    }

    /**
     * Stream Server-Sent Events.
     *
     * Sets the event-stream headers, including `X-Accel-Buffering: no` so nginx
     * does not buffer the stream. On failure one generic `event: error` frame is
     * written before the exception is rethrown for reporting.
     *
     * @param \Closure(): iterable<SseEvent> $events
     *
     * @return self
     */
    public static function sse(\Closure $events): self
    {
        return new self(
            static function () use ($events): \Generator {
                foreach ($events() as $event) {
                    yield $event->toString();
                }
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ],
            static fn (Throwable $e): iterable => [(new SseEvent(self::SSE_ERROR_PAYLOAD, 'error'))->toString()],
        );
    }

    /**
     * @return int
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @throws \InvalidArgumentException When $name or $value contains a control character.
     *
     * @return static
     */
    public function withHeader(string $name, string $value): static
    {
        HeaderValidator::assertValid($name, $value);

        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    /**
     * @return list<Cookie>
     */
    public function cookies(): array
    {
        return $this->cookies;
    }

    /**
     * @param string $name
     * @param string $value
     * @param int    $ttl
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httpOnly
     *
     * @return static
     */
    public function withCookie(
        string $name,
        string $value,
        int $ttl = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = false,
    ): static {
        $clone = clone $this;
        $clone->cookies[] = new Cookie($name, $value, $ttl, $path, $domain, $secure, $httpOnly);

        return $clone;
    }

    /**
     * Write each chunk produced by the factory.
     *
     * On ClientDisconnectedException the exception is rethrown at once — a
     * disconnect is not an error. On any other exception the onError frames are
     * written (a failure while writing them is swallowed so it cannot mask the
     * original) and the original exception is rethrown for reporting.
     *
     * @param \Closure(string): void $write
     *
     * @throws Throwable The exception raised by the chunk factory or by $write.
     *
     * @return void
     */
    public function writeBody(\Closure $write): void
    {
        try {
            foreach (($this->chunks)() as $chunk) {
                $write($chunk);
            }
        } catch (ClientDisconnectedException $e) {
            throw $e;
        } catch (Throwable $e) {
            if ($this->onError !== null) {
                try {
                    foreach (($this->onError)($e) as $frame) {
                        $write($frame);
                    }
                } catch (Throwable) {
                    // Must not mask the original failure.
                }
            }

            throw $e;
        }
    }

    /**
     * Build an RFC 6266 Content-Disposition value with an ASCII fallback and a
     * UTF-8 filename* parameter.
     *
     * The fallback keeps printable ASCII only and drops `"` and `\`, so the
     * quoted-string can never be broken out of. filename* is percent-encoded,
     * so neither parameter can carry CR/LF into the header.
     *
     * @param string $filename
     *
     * @return string
     */
    private static function contentDisposition(string $filename): string
    {
        $fallback = (string) preg_replace('/[^\x20-\x7E]|["\\\\]/', '', $filename);

        return sprintf('attachment; filename="%s"; filename*=UTF-8\'\'%s', $fallback, rawurlencode($filename));
    }
}
