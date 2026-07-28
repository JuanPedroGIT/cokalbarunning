<?php

declare(strict_types=1);

namespace App\Application\Notification\PreviewRecipients;

use App\Domain\Notification\Repository\EmailConfigRepositoryInterface;
use App\Domain\Notification\Repository\EmailSendLogRepositoryInterface;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Domain\Registration\Repository\RunnerRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class PreviewEmailRecipientsHandler
{
    public function __construct(
        private EmailConfigRepositoryInterface $emailConfigRepository,
        private RunnerRepositoryInterface $runnerRepository,
        private RaceEditionRepositoryInterface $raceEditionRepository,
        private EmailSendLogRepositoryInterface $emailSendLogRepository,
    ) {
    }

    public function __invoke(PreviewEmailRecipientsCommand $command): array
    {
        $config = $this->emailConfigRepository->findById($command->emailConfigId);
        if ($config === null) {
            throw new \RuntimeException('Email configuration not found');
        }

        $edition = $this->raceEditionRepository->findById(RaceEditionId::fromString($config->raceEditionId()));
        if ($edition === null) {
            throw new \RuntimeException('Edition not found');
        }

        $raceEditionId = $edition->id()->value();
        $runners = $this->runnerRepository->findByEditionIdAndBibRange(
            raceEditionId: $raceEditionId,
            bibFrom: $command->bibFrom,
            bibTo: $command->bibTo,
        );

        $type = $config->type();
        $items = array_map(function ($runner) use ($type, $raceEditionId) {
            $runnerEmail = $runner->email() ?? '';
            $bibNumber = $runner->bibNumber();

            // Mismo cálculo que SendEmailCampaignHandler: reference = "0 - Nombre"
            $logReference = ($bibNumber !== null && trim($bibNumber, '0') === '')
                ? '0 - ' . $runner->fullName()
                : $bibNumber;

            // Obtener todos los logs para este email+tipo+reference (misma edición)
            $allLogs = $this->emailSendLogRepository->findAllByEmailTypeAndReference(
                $runnerEmail,
                $type,
                $logReference
            );

            // Filtrar por edición
            $editionLogs = array_filter($allLogs, fn ($log) => $log->raceEditionId() === $raceEditionId);
            $editionLogs = array_values($editionLogs); // reindexar

            // Contar enviados
            $sentCount = count(array_filter($editionLogs, fn ($log) => $log->status()->isSent()));

            // Último registro (el más reciente)
            $latest = $editionLogs !== [] ? $editionLogs[0] : null;

            return [
                'id' => $runner->id(),
                'firstName' => $runner->firstName(),
                'lastName' => $runner->lastName(),
                'fullName' => $runner->fullName(),
                'email' => $runner->email(),
                'reference' => $bibNumber,
                'club' => $runner->club(),
                'gender' => $runner->gender(),
                'category' => $runner->category(),
                'birthDate' => $runner->birthDate()?->format('Y-m-d'),
                'emailValid' => $runnerEmail !== '',
                'status' => $latest?->status()->value() ?? 'not_sent',
                'sentCount' => $sentCount,
                'errorMessage' => $latest?->errorMessage(),
                'sentAt' => $latest?->sentAt()?->format('Y-m-d H:i:s'),
            ];
        }, $runners);

        return [
            'edition' => [
                'id' => $edition->id()->value(),
                'name' => $edition->name(),
                'year' => $edition->year()->value(),
            ],
            'config' => $config->toArray(),
            'items' => $items,
        ];
    }
}
