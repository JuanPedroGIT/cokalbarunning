<?php

declare(strict_types=1);

namespace App\Application\Race\QueryHandler;

use App\Application\Race\Query\GetDocumentsByEditionQuery;
use App\Application\Race\Response\RaceDocumentResponseDto;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Race\Repository\RaceDocumentRepositoryInterface;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\EditionYear;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetDocumentsByEditionQueryHandler
{
    public function __construct(
        private RaceDocumentRepositoryInterface $repository,
        private RaceEditionRepositoryInterface $raceEditionRepository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(GetDocumentsByEditionQuery $query): array
    {
        $editionId = $query->editionId;

        if ($editionId === null && $query->year !== null) {
            $edition = $this->raceEditionRepository->findByYear(EditionYear::fromInt($query->year));
            if (!$edition) {
                return [];
            }
            $editionId = $edition->id()->value();
        }

        if ($editionId === null) {
            return [];
        }

        $documents = $this->repository->findByEditionId($editionId);

        return RaceDocumentResponseDto::fromDomainList(
            $documents,
            fn (string $path) => $this->storage->url($path)
        );
    }
}
