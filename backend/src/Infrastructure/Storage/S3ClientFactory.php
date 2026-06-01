<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use Aws\S3\S3Client;

final class S3ClientFactory
{
    public static function create(string $accountId, string $accessKeyId, string $accessKeySecret): S3Client
    {
        return new S3Client([
            'region' => 'auto',
            'version' => 'latest',
            'endpoint' => "https://{$accountId}.r2.cloudflarestorage.com",
            'credentials' => [
                'key' => $accessKeyId,
                'secret' => $accessKeySecret,
            ],
        ]);
    }
}
