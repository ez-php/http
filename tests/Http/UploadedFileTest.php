<?php

declare(strict_types=1);

namespace Tests\Http;

use EzPhp\Http\UploadedFile;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\TestCase;

/**
 * Class UploadedFileTest
 *
 * @package Tests\Http
 */
#[CoversClass(UploadedFile::class)]
final class UploadedFileTest extends TestCase
{
    /**
     * @return UploadedFile
     */
    private function makeValidFile(): UploadedFile
    {
        return new UploadedFile(
            originalName: 'photo.jpg',
            mimeType: 'image/jpeg',
            size: 2048,
            tmpName: '/tmp/phpXXXXXX',
            error: UPLOAD_ERR_OK,
        );
    }

    /**
     * @return void
     */
    public function test_original_name_returns_client_filename(): void
    {
        $this->assertSame('photo.jpg', $this->makeValidFile()->originalName());
    }

    /**
     * @return void
     */
    public function test_mime_type_returns_client_mime(): void
    {
        $this->assertSame('image/jpeg', $this->makeValidFile()->mimeType());
    }

    /**
     * @return void
     */
    public function test_size_returns_file_size_in_bytes(): void
    {
        $this->assertSame(2048, $this->makeValidFile()->size());
    }

    /**
     * @return void
     */
    public function test_error_returns_upload_error_code(): void
    {
        $this->assertSame(UPLOAD_ERR_OK, $this->makeValidFile()->error());
    }

    /**
     * @return void
     */
    public function test_is_valid_returns_true_when_no_error(): void
    {
        $this->assertTrue($this->makeValidFile()->isValid());
    }

    /**
     * @return void
     */
    public function test_is_valid_returns_false_when_upload_error(): void
    {
        $file = new UploadedFile(
            originalName: 'broken.txt',
            mimeType: 'text/plain',
            size: 0,
            tmpName: '',
            error: UPLOAD_ERR_PARTIAL,
        );

        $this->assertFalse($file->isValid());
    }

    /**
     * @return void
     */
    public function test_move_to_throws_when_file_has_upload_error(): void
    {
        $file = new UploadedFile(
            originalName: 'broken.txt',
            mimeType: 'text/plain',
            size: 0,
            tmpName: '',
            error: UPLOAD_ERR_INI_SIZE,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('upload error code');

        $file->moveTo('/tmp/dest.txt');
    }

    /**
     * @return void
     */
    public function test_move_to_throws_when_move_uploaded_file_fails(): void
    {
        // A valid error code but non-existent tmp_name will cause
        // move_uploaded_file() to return false in CLI context.
        $file = new UploadedFile(
            originalName: 'test.txt',
            mimeType: 'text/plain',
            size: 10,
            tmpName: '/tmp/nonexistent_phpXXX',
            error: UPLOAD_ERR_OK,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to move uploaded file');

        $file->moveTo('/tmp/dest_test.txt');
    }
}
