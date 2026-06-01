<?php

declare(strict_types=1);

namespace App\Application\Race\QueryHandler;

use App\Application\Race\Query\GetActiveEditionQuery;
use App\Application\Race\Response\RaceEditionResponseDto;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetActiveEditionQueryHandler
{
    public function __construct(
        private RaceEditionRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(GetActiveEditionQuery $query): ?RaceEditionResponseDto
    {
        $edition = $this->repository->findActive();

        return $edition !== null ? RaceEditionResponseDto::fromDomain($edition, $this->storage) : null;
    }
}
