<?php

declare(strict_types=1);

namespace App\Application\Registration\DeleteAllRunnersByEdition;

use App\Domain\Registration\Repository\RunnerRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteAllRunnersByEditionHandler
{
    public function __construct(
        private RunnerRepositoryInterface $runnerRepository,
    ) {
    }

    public function __invoke(DeleteAllRunnersByEditionCommand $command): int
    {
        $runners = $this->runnerRepository->findByEditionId($command->editionId);
        $count = 0;

        foreach ($runners as $runner) {
            $this->runnerRepository->remove($runner);
            $count++;
        }

        return $count;
    }
}
