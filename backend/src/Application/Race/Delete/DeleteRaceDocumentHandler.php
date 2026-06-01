<?php

declare(strict_types=1);

namespace App\Application\Race\Delete;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Race\Repository\RaceDocumentRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteRaceDocumentHandler
{
    public function __construct(
        private RaceDocumentRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(DeleteRaceDocumentCommand $command): void
    {
        $document = $this->repository->findById($command->id);
        if (!$document) {
            throw new \InvalidArgumentException('Document not found');
        }

        $this->storage->delete($document->filePath());
        $this->repository->remove($document);
    }
}
