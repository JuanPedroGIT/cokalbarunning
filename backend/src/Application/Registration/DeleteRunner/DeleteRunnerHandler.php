<?php

declare(strict_types=1);

namespace App\Application\Registration\DeleteRunner;

use App\Domain\Registration\Repository\RunnerRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteRunnerHandler
{
    public function __construct(
        private RunnerRepositoryInterface $runnerRepository,
    ) {
    }

    public function __invoke(DeleteRunnerCommand $command): void
    {
        $runner = $this->runnerRepository->findById($command->id);
        if ($runner === null) {
            throw new \RuntimeException('Runner not found');
        }

        $this->runnerRepository->remove($runner);
    }
}
