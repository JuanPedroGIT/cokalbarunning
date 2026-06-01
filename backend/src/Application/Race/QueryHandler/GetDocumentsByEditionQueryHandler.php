<?php

declare(strict_types=1);

namespace App\Application\Race\QueryHandler;

use App\Application\Race\Query\GetDocumentsByEditionQuery;
use App\Application\Race\Response\RaceDocumentResponseDto;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Race\Repository\RaceDocumentRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetDocumentsByEditionQueryHandler
{
    public function __construct(
        private RaceDocumentRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(GetDocumentsByEditionQuery $query): array
    {
        $documents = $this->repository->findByEditionId($query->editionId);

        return RaceDocumentResponseDto::fromDomainList(
            $documents,
            fn (string $path) => $this->storage->url($path)
        );
    }
}
