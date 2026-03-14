<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Class Response
 *
 * @package EzPhp\Http
 */
final class Response
{
    /**
     * @var array<string, string>
     */
    private array $headers = [];

    /**
     * Response Constructor
     *
     * @param string $body
     * @param int    $status
     */
    public function __construct(
        private string $body = '',
        private int $status = 200
    ) {
    }

    /**
     * @return int
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return Response
     */
    public function withHeader(string $name, string $value): Response
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }
}
