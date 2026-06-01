<?php

declare(strict_types=1);

namespace App\Application\Race\UploadImage;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Service\PathGenerator;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UploadRaceEditionImageHandler
{
    public function __construct(
        private RaceEditionRepositoryInterface $repository,
        private StoragePort $storage,
        private PathGenerator $pathGen,
    ) {
    }

    public function __invoke(UploadRaceEditionImageCommand $command): string
    {
        $edition = $this->repository->findById(RaceEditionId::fromString($command->editionId));
        if (!$edition) {
            throw new \RuntimeException('Edition not found');
        }

        $file = new UploadedFile($command->tmpPath, $command->originalName, $command->mimeType, null, true);
        $year = (int) $edition->year()->value();
        $ext = $file->guessExtension() ?: 'jpg';

        $filename = $command->type === 'poster'
            ? $this->pathGen->posterPath($year, $ext)
            : $this->pathGen->shirtPath($year, $ext);

        // Delete previous
        $previous = $command->type === 'poster' ? $edition->posterUrl() : $edition->shirtUrl();
        if ($previous !== null) {
            $this->storage->delete($previous);
        }

        $this->storage->store($file, $filename);

        if ($command->type === 'poster') {
            $edition->setPosterUrl($filename);
        } else {
            $edition->setShirtUrl($filename);
        }

        $this->repository->save($edition);

        return $this->storage->url($filename);
    }
}
