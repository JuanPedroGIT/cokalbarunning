<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use Aws\S3\S3Client;

final class R2FileLister
{
    public function __construct(
        private S3Client $s3Client,
        private string $bucket,
    ) {
    }

    /**
     * @return string[]
     */
    public function listImageKeys(string $prefix): array
    {
        $objects = [];
        $continuationToken = null;

        do {
            $args = [
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
                'MaxKeys' => 1000,
            ];
            if ($continuationToken !== null) {
                $args['ContinuationToken'] = $continuationToken;
            }
            $result = $this->s3Client->listObjectsV2($args);
            foreach ($result['Contents'] ?? [] as $obj) {
                $key = $obj['Key'];
                if ($key !== $prefix && preg_match('/\.(jpg|jpeg|png|webp)$/i', $key)) {
                    $objects[] = $key;
                }
            }
            $continuationToken = $result['NextContinuationToken'] ?? null;
        } while ($continuationToken !== null);

        return $objects;
    }
}
