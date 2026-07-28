<?php

declare(strict_types=1);

namespace App\Application\Registration\GetRunnersByEdition;

use App\Domain\Registration\Repository\RunnerRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetRunnersByEditionQueryHandler
{
    public function __construct(
        private RunnerRepositoryInterface $runnerRepository,
    ) {
    }

    public function __invoke(GetRunnersByEditionQuery $query): array
    {
        $runners = $this->runnerRepository->findByEditionId($query->editionId);

        return array_map(static function ($runner) {
            return [
                'id' => $runner->id(),
                'firstName' => $runner->firstName(),
                'lastName' => $runner->lastName(),
                'fullName' => $runner->fullName(),
                'bibNumber' => $runner->bibNumber(),
                'club' => $runner->club(),
                'email' => $runner->email(),
                'gender' => $runner->gender(),
                'category' => $runner->category(),
                'birthDate' => $runner->birthDate()?->format('Y-m-d'),
            ];
        }, $runners);
    }
}
