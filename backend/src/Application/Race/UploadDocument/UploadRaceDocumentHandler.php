<?php

declare(strict_types=1);

namespace App\Application\Race\UploadDocument;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Service\PathGenerator;
use App\Domain\Race\Entity\RaceDocument;
use App\Domain\Race\Repository\RaceDocumentRepositoryInterface;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UploadRaceDocumentHandler
{
    public function __construct(
        private RaceDocumentRepositoryInterface $repository,
        private RaceEditionRepositoryInterface $raceEditionRepository,
        private StoragePort $storage,
        private PathGenerator $pathGen,
    ) {
    }

    public function __invoke(UploadRaceDocumentCommand $command): string
    {
        $editionYear = null;
        if ($command->editionId !== null) {
            $edition = $this->raceEditionRepository->findById(RaceEditionId::fromString($command->editionId));
            if (!$edition) {
                throw new \RuntimeException('Race edition not found');
            }
            $editionYear = (int) $edition->year()->value();
        }

        $file = new UploadedFile(
            $command->tmpPath,
            $command->originalName,
            $command->mimeType,
            null,
            true
        );

        $extension = $file->guessExtension() ?: 'pdf';
        $filename = $this->pathGen->documentPath($editionYear, $command->type, $extension);

        $this->storage->store($file, $filename);

        $document = RaceDocument::create(
            id: Uuid::uuid4()->toString(),
            name: $command->name,
            type: $command->type,
            filePath: $filename,
            editionId: $command->editionId,
        );

        $this->repository->save($document);

        return $document->id();
    }
}
