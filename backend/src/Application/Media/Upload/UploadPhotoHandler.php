<?php

declare(strict_types=1);

namespace App\Application\Media\Upload;

use App\Domain\Media\Entity\Photo;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Repository\PhotoRepositoryInterface;
use App\Domain\Media\Service\ImageProcessorInterface;
use App\Domain\Media\Service\PathGenerator;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UploadPhotoHandler
{
    public function __construct(
        private PhotoRepositoryInterface $repository,
        private RaceEditionRepositoryInterface $raceEditionRepository,
        private StoragePort $storage,
        private ImageProcessorInterface $imageProcessor,
        private PathGenerator $pathGen,
    ) {
    }

    public function __invoke(UploadPhotoCommand $command): string
    {
        $edition = $this->raceEditionRepository->findById(RaceEditionId::fromString($command->raceEditionId));
        if (!$edition) {
            throw new \RuntimeException('Race edition not found');
        }

        $file = new UploadedFile(
            $command->tmpPath,
            $command->originalName,
            $command->mimeType,
            null,
            true
        );

        $year = (int) $edition->year()->value();
        $ext = $file->guessExtension() ?: 'jpg';
        $originalPath = $this->pathGen->photoPath($year, $ext);

        $thumbPath = null;
        if (str_starts_with($command->mimeType, 'image/')) {
            $thumbFilename = $this->pathGen->thumbnailPath($year);
            $thumbTempPath = $this->imageProcessor->createThumbnail($file, 400, 85);
            try {
                $thumbUploadedFile = new UploadedFile($thumbTempPath, basename($thumbTempPath), 'image/webp', null, true);
                $this->storage->store($thumbUploadedFile, $thumbFilename);
            } finally {
                if (file_exists($thumbTempPath)) {
                    unlink($thumbTempPath);
                }
            }
            $thumbPath = $thumbFilename;
        }

        $this->storage->store($file, $originalPath);

        $photo = new Photo(
            id: Uuid::uuid4()->toString(),
            originalPath: $originalPath,
            thumbPath: $thumbPath,
            raceEditionId: $command->raceEditionId ? RaceEditionId::fromString($command->raceEditionId) : null,
            altText: $command->altText,
            isFeatured: $command->isFeatured,
            sortOrder: $command->sortOrder,
        );

        $this->repository->save($photo);

        return $photo->id();
    }
}
