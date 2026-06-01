<?php

declare(strict_types=1);

namespace App\Domain\Media\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface ImageProcessorInterface
{
    /**
     * Creates a thumbnail from an uploaded image.
     *
     * @param UploadedFile $file The original uploaded image
     * @param int $maxWidth Maximum width in pixels (height is proportional)
     * @param int $quality WebP quality (0-100)
     * @return string Path to the temporary generated thumbnail file
     */
    public function createThumbnail(UploadedFile $file, int $maxWidth, int $quality): string;
}
