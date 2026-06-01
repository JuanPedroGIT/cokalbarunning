<?php

declare(strict_types=1);

namespace App\Application\Race\Update;

use App\Domain\Race\Repository\RaceDocumentRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateRaceDocumentHandler
{
    public function __construct(
        private RaceDocumentRepositoryInterface $repository,
    ) {
    }

    public function __invoke(UpdateRaceDocumentCommand $command): void
    {
        $document = $this->repository->findById($command->id);
        if (!$document) {
            throw new \InvalidArgumentException('Document not found');
        }

        $document->update(
            name: $command->name ?? $document->name(),
            type: $command->type ?? $document->type()->value(),
            editionId: $command->editionId !== null ? ($command->editionId ?: null) : $document->editionId(),
        );

        $this->repository->save($document);
    }
}
