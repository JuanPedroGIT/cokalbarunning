<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Domain\Media\Port\StoragePort;
use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class R2StorageAdapter implements StoragePort
{
    private S3Client $client;
    private string $bucket;
    private string $publicUrl;

    public function __construct(
        string $accountId,
        string $accessKeyId,
        string $accessKeySecret,
        string $bucket,
        string $publicUrl,
    ) {
        $this->bucket = $bucket;
        $this->publicUrl = rtrim($publicUrl, '/');

        $this->client = new S3Client([
            'region' => 'auto',
            'version' => 'latest',
            'endpoint' => "https://{$accountId}.r2.cloudflarestorage.com",
            'credentials' => [
                'key' => $accessKeyId,
                'secret' => $accessKeySecret,
            ],
        ]);
    }

    public function store(UploadedFile $file, string $path): string
    {
        $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $path,
            'Body' => fopen($file->getRealPath(), 'rb'),
            'ContentType' => $file->getMimeType(),
        ]);

        return $path;
    }

    public function delete(string $path): void
    {
        $this->client->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $path,
        ]);
    }

    public function url(string $path): string
    {
        return $this->publicUrl . '/' . ltrim($path, '/');
    }
}
