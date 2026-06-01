<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Media\Service\ImageProcessorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class GdImageProcessor implements ImageProcessorInterface
{
    public function createThumbnail(UploadedFile $file, int $maxWidth, int $quality): string
    {
        $sourcePath = $file->getRealPath();
        if ($sourcePath === false) {
            throw new \RuntimeException('Unable to get real path of uploaded file');
        }

        $sourceInfo = getimagesize($sourcePath);
        if ($sourceInfo === false) {
            throw new \RuntimeException('Unable to get image dimensions');
        }

        [$sourceWidth, $sourceHeight, $sourceType] = $sourceInfo;

        // Calculate new dimensions maintaining aspect ratio
        if ($sourceWidth <= $maxWidth) {
            $newWidth = $sourceWidth;
            $newHeight = $sourceHeight;
        } else {
            $ratio = $maxWidth / $sourceWidth;
            $newWidth = $maxWidth;
            $newHeight = (int) round($sourceHeight * $ratio);
        }

        // Create source image based on type
        $sourceImage = match ($sourceType) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
            IMAGETYPE_GIF => imagecreatefromgif($sourcePath),
            default => throw new \RuntimeException('Unsupported image type: ' . $sourceType),
        };

        if ($sourceImage === false) {
            throw new \RuntimeException('Failed to create image from source');
        }

        // Create destination image
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
        if ($thumbnail === false) {
            imagedestroy($sourceImage);
            throw new \RuntimeException('Failed to create thumbnail canvas');
        }

        // Preserve transparency for PNG/WebP
        if ($sourceType === IMAGETYPE_PNG || $sourceType === IMAGETYPE_WEBP) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }

        // Resize
        imagecopyresampled(
            $thumbnail,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            $sourceWidth,
            $sourceHeight
        );

        // Save as WebP to temp file
        $tempPath = tempnam(sys_get_temp_dir(), 'thumb_') . '.webp';
        imagewebp($thumbnail, $tempPath, $quality);

        // Cleanup
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);

        return $tempPath;
    }
}
