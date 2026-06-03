<?php

declare(strict_types=1);

namespace App\Application\Race\QueryHandler;

use App\Application\Race\Query\GetLatestEditionQuery;
use App\Application\Race\Response\RaceEditionResponseDto;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetLatestEditionQueryHandler
{
    public function __construct(
        private RaceEditionRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(GetLatestEditionQuery $query): ?RaceEditionResponseDto
    {
        $editions = $this->repository->findAllOrdered();

        return isset($editions[0]) ? RaceEditionResponseDto::fromDomain($editions[0], $this->storage) : null;
    }
}
