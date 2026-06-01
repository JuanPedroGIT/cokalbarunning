<?php

declare(strict_types=1);

namespace App\Application\Results\QueryHandler;

use App\Application\Results\Query\GetResultsByYearQuery;
use App\Application\Results\Response\ResultResponseDto;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\EditionYear;
use App\Domain\Results\Repository\ResultRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetResultsByYearQueryHandler
{
    public function __construct(
        private RaceEditionRepositoryInterface $raceEditionRepository,
        private ResultRepositoryInterface $resultRepository,
    ) {
    }

    /**
     * @return ResultResponseDto[]
     */
    public function __invoke(GetResultsByYearQuery $query): array
    {
        $edition = $this->raceEditionRepository->findByYear(EditionYear::fromInt($query->year));
        if ($edition === null) {
            return [];
        }

        $results = $this->resultRepository->findByRaceEdition($edition->id());

        return ResultResponseDto::fromDomainList($results);
    }
}
