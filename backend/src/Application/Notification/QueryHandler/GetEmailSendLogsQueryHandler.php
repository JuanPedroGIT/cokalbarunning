<?php

declare(strict_types=1);

namespace App\Application\Notification\QueryHandler;

use App\Application\Notification\Query\GetEmailSendLogsQuery;
use App\Application\Notification\Response\EmailSendLogResponseDto;
use App\Domain\Notification\Repository\EmailSendLogRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetEmailSendLogsQueryHandler
{
    public function __construct(
        private EmailSendLogRepositoryInterface $repository,
    ) {
    }

    /**
     * @return EmailSendLogResponseDto[]
     */
    public function __invoke(GetEmailSendLogsQuery $query): array
    {
        $logs = $this->repository->findByTypeAndRaceEditionId($query->type, $query->raceEditionId);

        return array_map(
            fn ($log) => EmailSendLogResponseDto::fromDomain($log),
            $logs
        );
    }
}
