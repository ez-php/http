<?php

declare(strict_types=1);

namespace EzPhp\Http;

use RuntimeException;

/**
 * Class UploadedFile
 *
 * Lightweight value object representing a file uploaded via an HTTP multipart
 * form. Wraps the corresponding $_FILES entry without depending on PSR-7.
 *
 * Files that did not arrive through PHP's own upload handling (e.g. converted
 * from a PSR-7 request under RoadRunner/Swoole) are created via fromLocalFile():
 * move_uploaded_file() refuses those, so moveTo() renames them instead.
 *
 * @package EzPhp\Http
 */
final readonly class UploadedFile
{
    /**
     * UploadedFile Constructor
     *
     * @param string $originalName Client-supplied filename (e.g. "photo.jpg").
     * @param string $mimeType     MIME type reported by the client (e.g. "image/jpeg").
     * @param int    $size         File size in bytes.
     * @param string $tmpName      Path to the temporary file on the server.
     * @param int    $error        One of the UPLOAD_ERR_* constants.
     * @param bool   $sapiUpload   Whether PHP's SAPI received the file (moveTo() then uses
     *                             move_uploaded_file()); false only via fromLocalFile().
     */
    public function __construct(
        private string $originalName,
        private string $mimeType,
        private int $size,
        private string $tmpName,
        private int $error,
        private bool $sapiUpload = true,
    ) {
    }

    /**
     * Create an upload backed by a local file that PHP's SAPI did not receive
     * itself (a PSR-7 bridge copy, a worker server's temp file). moveTo() renames
     * such a file instead of calling move_uploaded_file(), which would refuse it.
     *
     * @param string $originalName Client-supplied filename.
     * @param string $mimeType     MIME type reported by the client.
     * @param int    $size         File size in bytes.
     * @param string $tmpName      Path to the local file.
     * @param int    $error        One of the UPLOAD_ERR_* constants.
     *
     * @return self
     */
    public static function fromLocalFile(string $originalName, string $mimeType, int $size, string $tmpName, int $error): self
    {
        return new self($originalName, $mimeType, $size, $tmpName, $error, false);
    }

    /**
     * Return the server-side path of the temporary upload file.
     *
     * @return string
     */
    public function tmpName(): string
    {
        return $this->tmpName;
    }

    /**
     * Return the original filename as provided by the client.
     *
     * @return string
     */
    public function originalName(): string
    {
        return $this->originalName;
    }

    /**
     * Return the MIME type reported by the client.
     *
     * Note: this value is client-supplied and should not be trusted for
     * security decisions. Use a server-side MIME detection library instead.
     *
     * @return string
     */
    public function mimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Return the file size in bytes.
     *
     * @return int
     */
    public function size(): int
    {
        return $this->size;
    }

    /**
     * Return the upload error code (one of the UPLOAD_ERR_* constants).
     *
     * @return int
     */
    public function error(): int
    {
        return $this->error;
    }

    /**
     * Return true when the file was uploaded without errors.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * Move the uploaded file to a new location.
     *
     * Uses move_uploaded_file() which also verifies that the file was
     * legitimately uploaded via HTTP POST (SAPI check). A file created via
     * fromLocalFile() is renamed instead.
     *
     * @param string $destination Absolute path to the target file.
     *
     * @return void
     * @throws RuntimeException When the file has an upload error or the move fails.
     */
    public function moveTo(string $destination): void
    {
        if (!$this->isValid()) {
            throw new RuntimeException(
                "Cannot move uploaded file: upload error code {$this->error}."
            );
        }

        $moved = $this->sapiUpload
            ? move_uploaded_file($this->tmpName, $destination)
            : $this->renameLocalFile($destination);

        if (!$moved) {
            throw new RuntimeException(
                "Failed to move uploaded file to '{$destination}'."
            );
        }
    }

    /**
     * rename() with its warning captured instead of emitted — the caller turns a
     * false return into an exception.
     *
     * @param string $destination
     *
     * @return bool
     */
    private function renameLocalFile(string $destination): bool
    {
        set_error_handler(static fn (): bool => true);

        try {
            return rename($this->tmpName, $destination);
        } finally {
            restore_error_handler();
        }
    }
}
