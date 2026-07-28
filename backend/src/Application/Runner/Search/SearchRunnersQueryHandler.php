<?php

declare(strict_types=1);

namespace App\Application\Runner\Search;

use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Registration\Repository\RunnerRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SearchRunnersQueryHandler
{
    public function __construct(
        private RunnerRepositoryInterface $runnerRepository,
        private RaceEditionRepositoryInterface $raceEditionRepository,
    ) {
    }

    public function __invoke(SearchRunnersQuery $query): array
    {
        $editionId = $query->editionId;
        if ($editionId === null) {
            $activeEdition = $this->raceEditionRepository->findActive();
            if ($activeEdition === null) {
                return [];
            }
            $editionId = $activeEdition->id()->value();
        }

        $runners = $this->runnerRepository->searchByEditionId(
            raceEditionId: $editionId,
            name: $query->name,
            bib: $query->bib,
            maxResults: 50,
        );

        return array_map(static function ($runner) {
            return [
                'id' => $runner->id(),
                'firstName' => $runner->firstName(),
                'lastName' => $runner->lastName(),
                'fullName' => trim($runner->firstName() . ' ' . $runner->lastName()),
                'bibNumber' => $runner->bibNumber(),
                'club' => $runner->club(),
                'gender' => $runner->gender(),
                'category' => $runner->category(),
            ];
        }, $runners);
    }
}
