<?php

declare(strict_types=1);

namespace App\Application\Notification\SendCampaign;

use App\Application\Notification\Command\CreateEmailSendLogCommand;
use App\Domain\Notification\Repository\EmailConfigRepositoryInterface;
use App\Domain\Notification\Repository\EmailSendLogRepositoryInterface;
use App\Domain\Notification\ValueObject\EmailType;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Domain\Registration\Repository\RunnerRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

#[AsMessageHandler]
final class SendEmailCampaignHandler
{
    public function __construct(
        private EmailConfigRepositoryInterface $emailConfigRepository,
        private EmailSendLogRepositoryInterface $emailSendLogRepository,
        private RunnerRepositoryInterface $runnerRepository,
        private RaceEditionRepositoryInterface $raceEditionRepository,
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(SendEmailCampaignCommand $command): array
    {
        $config = $this->emailConfigRepository->findById($command->emailConfigId);
        if ($config === null) {
            throw new \RuntimeException('Email configuration not found');
        }

        $type = $config->type();
        $metadata = $command->metadata;
        $force = $command->force;
        $sentBy = $command->sentBy;
        $raceEditionId = $command->editionId;

        $queued = 0;
        $skipped = 0;
        $queuedInstructions = 0;

        // Process each runner ID individually
        foreach ($command->runnerIds as $runnerId) {
            $runner = $this->runnerRepository->findById($runnerId);
            if ($runner === null || $runner->email() === null || $runner->email() === '') {
                $skipped++;
                continue;
            }
            $email = $runner->email();
            $name = $runner->fullName();
            $bibNumber = $runner->bibNumber();

            // Para dorsales 0/00/000, usar un reference único con el nombre
            $isZeroBib = $bibNumber !== null && trim($bibNumber, '0') === '';
            $logReference = $isZeroBib
                ? '0 - ' . $name
                : $bibNumber;

            // Migrar log antiguo con reference="0" al nuevo formato único
            if ($isZeroBib) {
                $oldLog = $this->emailSendLogRepository->findByEmailTypeAndReference(
                    $email, $type, $bibNumber
                );
                if ($oldLog !== null) {
                    // Actualizar el reference al formato único para este runner
                    $oldLog->markAsPending();
                    if ($sentBy !== null && $sentBy !== '') {
                        $oldLog->assignSentBy($sentBy);
                    }
                    // Migrar el reference para que futuros lookups sean precisos
                    $oldLog->updateReference($logReference);
                    $this->emailSendLogRepository->save($oldLog);
                    $queued++;
                    continue;
                }
            }

            $runnerMeta = array_merge($metadata, [
                'runnerId' => $runnerId,
                'bibNumber' => $bibNumber,
            ]);

            $created = $this->createOrUpdateLog(
                email: $email,
                name: $name,
                reference: $logReference,
                type: $type,
                raceEditionId: $raceEditionId,
                metadata: $runnerMeta,
                sentBy: $sentBy,
                force: $force,
            );

            if ($created === null) {
                $skipped++;
                continue;
            }

            $queued++;

            if ($type === EmailType::RAFFLE) {
                $this->createOrUpdateLog(
                    email: $email,
                    name: $name,
                    reference: $reference,
                    type: EmailType::LAST_INSTRUCTIONS,
                    raceEditionId: $raceEditionId,
                    metadata: [],
                    sentBy: $sentBy,
                    force: $force,
                );
                $queuedInstructions++;
            }
        }

        // Process runnerEmails by lookup in the edition
        foreach ($command->runnerEmails as $email) {
            $runner = $this->runnerRepository->findByEmailAndEditionId($email, $raceEditionId);
            if ($runner !== null && $runner->email() !== null && $runner->email() !== '') {
                $email = $runner->email();
                $name = $runner->fullName();
                $reference = $runner->bibNumber();
            } else {
                $name = $email;
                $reference = null;
            }

            $created = $this->createOrUpdateLog(
                email: $email,
                name: $name,
                reference: $reference,
                type: $type,
                raceEditionId: $raceEditionId,
                metadata: $metadata,
                sentBy: $sentBy,
                force: $force,
            );

            if ($created === null) {
                $skipped++;
                continue;
            }

            $queued++;

            if ($type === EmailType::RAFFLE) {
                $instructionsCreated = $this->createOrUpdateLog(
                    email: $email,
                    name: $name,
                    reference: $reference,
                    type: EmailType::LAST_INSTRUCTIONS,
                    raceEditionId: $raceEditionId,
                    metadata: [],
                    sentBy: $sentBy,
                    force: $force,
                );
                if ($instructionsCreated !== null) {
                    $queuedInstructions++;
                }
            }
        }

        if ($type === EmailType::BIB && $raceEditionId !== '' && $queued > 0) {
            $edition = $this->raceEditionRepository->findById(RaceEditionId::fromString($raceEditionId));
            if ($edition !== null && !$edition->showBibSearch()) {
                $edition->enableBibSearch();
                $this->raceEditionRepository->save($edition);
            }
        }

        return [
            'queued' => $queued,
            'skipped' => $skipped,
            'queuedInstructions' => $queuedInstructions,
        ];
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function createOrUpdateLog(
        string $email,
        string $name,
        ?string $reference,
        string $type,
        ?string $raceEditionId,
        array $metadata,
        ?string $sentBy,
        bool $force,
    ): ?string {
        $existing = $this->emailSendLogRepository->findByEmailTypeAndReference($email, $type, $reference);
        if ($existing !== null && $existing->status()->isSent() && !$force) {
            return null;
        }

        $isResend = $existing !== null && $existing->status()->isSent() && $force;

        if ($existing === null || $isResend) {
            $logId = Uuid::uuid4()->toString();
            $envelope = $this->commandBus->dispatch(new CreateEmailSendLogCommand(
                id: $logId,
                type: $type,
                recipientEmail: $email,
                recipientName: $name,
                reference: $reference,
                raceEditionId: $raceEditionId,
                sentBy: $sentBy,
                metadata: $metadata,
            ));

            return $envelope->last(HandledStamp::class)?->getResult() ?? $logId;
        }

        $existing->markAsPending();
        if ($sentBy !== null && $sentBy !== '') {
            $existing->assignSentBy($sentBy);
        }
        $this->emailSendLogRepository->save($existing);

        return $existing->id();
    }
}
