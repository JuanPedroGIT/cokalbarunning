<?php

declare(strict_types=1);

namespace App\Application\Race\QueryHandler;

use App\Application\Race\Query\GetGeneralDocumentsQuery;
use App\Application\Race\Response\RaceDocumentResponseDto;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Race\Repository\RaceDocumentRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetGeneralDocumentsQueryHandler
{
    public function __construct(
        private RaceDocumentRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(GetGeneralDocumentsQuery $query): array
    {
        $documents = $this->repository->findGeneral();

        return RaceDocumentResponseDto::fromDomainList(
            $documents,
            fn (string $path) => $this->storage->url($path)
        );
    }
}
