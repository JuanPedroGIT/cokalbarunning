<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Storage;

use App\Infrastructure\Storage\R2FileLister;
use Aws\S3\S3Client;
use PHPUnit\Framework\TestCase;

final class R2FileListerTest extends TestCase
{
    public function testListImageKeysFiltersImagesByExtension(): void
    {
        $s3Client = $this->createMock(S3Client::class);

        $s3Client->expects($this->once())
            ->method('__call')
            ->with('listObjectsV2', [[
                'Bucket' => 'test-bucket',
                'Prefix' => 'un-nuevo-impulso/race/2026/images/',
                'MaxKeys' => 1000,
            ]])
            ->willReturn([
                'Contents' => [
                    ['Key' => 'un-nuevo-impulso/race/2026/images/'],
                    ['Key' => 'un-nuevo-impulso/race/2026/images/photo1.jpg'],
                    ['Key' => 'un-nuevo-impulso/race/2026/images/photo2.png'],
                    ['Key' => 'un-nuevo-impulso/race/2026/images/photo3.webp'],
                    ['Key' => 'un-nuevo-impulso/race/2026/images/readme.txt'],
                ],
            ]);

        $lister = new R2FileLister($s3Client, 'test-bucket');
        $keys = $lister->listImageKeys('un-nuevo-impulso/race/2026/images/');

        $this->assertCount(3, $keys);
        $this->assertContains('un-nuevo-impulso/race/2026/images/photo1.jpg', $keys);
        $this->assertContains('un-nuevo-impulso/race/2026/images/photo2.png', $keys);
        $this->assertContains('un-nuevo-impulso/race/2026/images/photo3.webp', $keys);
    }

    public function testListImageKeysExcludesPrefixDirectoryEntry(): void
    {
        $prefix = 'un-nuevo-impulso/race/2025/images/';

        $s3Client = $this->createMock(S3Client::class);
        $s3Client->method('__call')
            ->willReturn([
                'Contents' => [
                    ['Key' => $prefix],
                    ['Key' => $prefix . 'image1.jpg'],
                ],
            ]);

        $lister = new R2FileLister($s3Client, 'test-bucket');
        $keys = $lister->listImageKeys($prefix);

        $this->assertCount(1, $keys);
        $this->assertSame($prefix . 'image1.jpg', $keys[0]);
    }

    public function testListImageKeysReturnsEmptyWhenNoContents(): void
    {
        $s3Client = $this->createMock(S3Client::class);
        $s3Client->method('__call')
            ->willReturn([]);

        $lister = new R2FileLister($s3Client, 'test-bucket');
        $keys = $lister->listImageKeys('empty-prefix/');

        $this->assertCount(0, $keys);
    }
}
