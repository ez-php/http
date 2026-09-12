<?php

declare(strict_types=1);

namespace EzPhp\Http;

/**
 * Class ClientDisconnectedException
 *
 * Thrown by ResponseEmitter when the client has gone away mid-body. Not an
 * error: it is never reported and never produces stream error frames.
 *
 * @package EzPhp\Http
 */
final class ClientDisconnectedException extends \RuntimeException
{
}
