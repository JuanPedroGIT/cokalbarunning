<?php

declare(strict_types=1);

namespace App\Application\SocialPublishing\UpdateStatus;

use App\Domain\SocialPublishing\Exception\SocialPublishingException;
use App\Domain\SocialPublishing\Repository\SocialPublishLogRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateSocialPublishStatusHandler
{
    public function __construct(
        private SocialPublishLogRepositoryInterface $logRepository,
    ) {
    }

    public function __invoke(UpdateSocialPublishStatusCommand $command): void
    {
        $log = $this->logRepository->findById($command->logId);
        if ($log === null) {
            throw SocialPublishingException::logNotFound();
        }

        $log->updateStatus($command->status, $command->externalUrl);
        $this->logRepository->save($log);
    }
}
