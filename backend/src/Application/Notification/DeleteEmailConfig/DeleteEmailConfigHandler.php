<?php

declare(strict_types=1);

namespace App\Application\Notification\DeleteEmailConfig;

use App\Domain\Notification\Repository\EmailConfigRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteEmailConfigHandler
{
    public function __construct(
        private EmailConfigRepositoryInterface $emailConfigRepository,
    ) {
    }

    public function __invoke(DeleteEmailConfigCommand $command): void
    {
        $config = $this->emailConfigRepository->findById($command->id);
        if ($config === null) {
            throw new \RuntimeException('Email configuration not found');
        }

        $this->emailConfigRepository->remove($config);
    }
}
