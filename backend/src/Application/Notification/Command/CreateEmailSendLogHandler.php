<?php

declare(strict_types=1);

namespace App\Application\Notification\Command;

use App\Domain\Notification\Entity\EmailSendLog;
use App\Domain\Notification\Repository\EmailSendLogRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateEmailSendLogHandler
{
    public function __construct(
        private EmailSendLogRepositoryInterface $repository,
    ) {
    }

    public function __invoke(CreateEmailSendLogCommand $command): string
    {
        $log = EmailSendLog::create(
            id: $command->id,
            type: $command->type,
            recipientEmail: $command->recipientEmail,
            recipientName: $command->recipientName,
            reference: $command->reference,
            raceEditionId: $command->raceEditionId,
            metadata: $command->metadata,
        );

        if ($command->sentBy !== null && $command->sentBy !== '') {
            $log->assignSentBy($command->sentBy);
        }

        $this->repository->save($log);

        return $log->id();
    }
}
