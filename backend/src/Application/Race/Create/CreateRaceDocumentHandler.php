<?php

declare(strict_types=1);

namespace App\Application\Race\Create;

use App\Domain\Race\Entity\RaceDocument;
use App\Domain\Race\Repository\RaceDocumentRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateRaceDocumentHandler
{
    public function __construct(
        private RaceDocumentRepositoryInterface $repository,
    ) {
    }

    public function __invoke(CreateRaceDocumentCommand $command): string
    {
        $document = RaceDocument::create(
            id: Uuid::uuid4()->toString(),
            name: $command->name,
            type: $command->type,
            filePath: $command->filePath,
            editionId: $command->editionId,
        );

        $this->repository->save($document);

        return $document->id();
    }
}
