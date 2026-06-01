<?php

declare(strict_types=1);

namespace App\Application\Race\QueryHandler;

use App\Application\Race\Query\GetEditionByYearQuery;
use App\Application\Race\Response\RaceEditionResponseDto;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\EditionYear;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetEditionByYearQueryHandler
{
    public function __construct(
        private RaceEditionRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(GetEditionByYearQuery $query): ?RaceEditionResponseDto
    {
        $edition = $this->repository->findByYear(EditionYear::fromInt($query->year));

        return $edition !== null ? RaceEditionResponseDto::fromDomainDetailed($edition, $this->storage) : null;
    }
}
