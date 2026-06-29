<?php

declare(strict_types=1);

namespace App\Application\Notification\GetSentCounts;

use App\Domain\Notification\Repository\EmailSendLogRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetEmailSentCountsQueryHandler
{
    public function __construct(
        private EmailSendLogRepositoryInterface $emailSendLogRepository,
    ) {
    }

    public function __invoke(GetEmailSentCountsQuery $query): array
    {
        $logs = $query->raceEditionId !== null && $query->raceEditionId !== ''
            ? $this->emailSendLogRepository->findByTypeAndRaceEditionId($query->type, $query->raceEditionId)
            : $this->emailSendLogRepository->findAll();

        $counts = [];

        foreach ($logs as $log) {
            if (!$log->status()->isSent()) {
                continue;
            }
            if ($log->type()->value() !== $query->type) {
                continue;
            }
            $email = $log->recipientEmail();
            $counts[$email] = ($counts[$email] ?? 0) + 1;
        }

        $data = [];
        foreach ($counts as $email => $count) {
            $data[] = ['email' => $email, 'count' => $count];
        }

        return $data;
    }
}
