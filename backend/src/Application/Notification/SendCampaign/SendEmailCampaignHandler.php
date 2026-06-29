<?php

declare(strict_types=1);

namespace App\Application\Notification\SendCampaign;

use App\Application\Notification\Command\CreateEmailSendLogCommand;
use App\Domain\Notification\Repository\EmailSendLogRepositoryInterface;
use App\Domain\Notification\ValueObject\EmailType;
use App\Entity\Runner;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

#[AsMessageHandler]
final class SendEmailCampaignHandler
{
    public function __construct(
        private EmailSendLogRepositoryInterface $emailSendLogRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(SendEmailCampaignCommand $command): array
    {
        $items = $command->items;
        $type = $command->type;
        $metadata = $command->metadata;
        $force = $command->force;
        $sentBy = $command->sentBy;
        $raceEditionId = $command->editionId;

        $queued = 0;
        $skipped = 0;
        $queuedInstructions = 0;

        foreach ($items as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $email = trim((string) ($item['email'] ?? ''));
            $firstName = trim((string) ($item['firstName'] ?? ''));
            $lastName = trim((string) ($item['lastName'] ?? ''));
            $fullName = trim((string) ($item['fullName'] ?? ''));
            $reference = isset($item['reference']) ? trim((string) $item['reference']) : null;

            if ($email === '') {
                continue;
            }

            $name = $fullName !== '' ? $fullName : trim($firstName . ' ' . $lastName);
            if ($name === '') {
                continue;
            }

            if ($raceEditionId !== null && $type !== EmailType::GENERIC && $type !== EmailType::THANKS) {
                $this->upsertRunner($item, $raceEditionId);
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

        if ($type === EmailType::BIB && $raceEditionId !== null && $queued > 0) {
            $ormEdition = $this->entityManager->getRepository(\App\Entity\RaceEdition::class)->find($raceEditionId);
            if ($ormEdition !== null && !$ormEdition->isShowBibSearch()) {
                $ormEdition->setShowBibSearch(true);
            }
        }

        $this->entityManager->flush();

        return [
            'queued' => $queued,
            'skipped' => $skipped,
            'queuedInstructions' => $queuedInstructions,
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    private function upsertRunner(array $item, string $raceEditionId): void
    {
        $email = trim((string) ($item['email'] ?? ''));
        if ($email === '') {
            return;
        }

        $firstName = trim((string) ($item['firstName'] ?? ''));
        $lastName = trim((string) ($item['lastName'] ?? ''));
        $fullName = trim((string) ($item['fullName'] ?? ''));

        if ($firstName === '' && $fullName !== '') {
            $parts = explode(' ', $fullName, 2);
            $firstName = $parts[0];
            $lastName = $parts[1] ?? '';
        }

        if ($firstName === '') {
            return;
        }

        $reference = isset($item['reference']) ? trim((string) $item['reference']) : null;
        $club = isset($item['club']) ? trim((string) $item['club']) : null;
        $gender = isset($item['gender']) ? trim((string) $item['gender']) : null;
        $category = isset($item['category']) ? trim((string) $item['category']) : null;

        $repository = $this->entityManager->getRepository(Runner::class);

        if ($reference !== null && $reference !== '' && trim($reference, '0') !== '') {
            $existingByBib = $repository->findOneBy([
                'raceEditionId' => $raceEditionId,
                'bibNumber' => $reference,
            ]);
            if ($existingByBib !== null) {
                return;
            }
        }

        $runner = $repository->findOneBy([
            'email' => $email,
            'raceEditionId' => $raceEditionId,
        ]);

        if ($runner === null) {
            $runner = new Runner();
            $runner->setId(Uuid::uuid4()->toString());
        }

        $runner->setFirstName($firstName);
        $runner->setLastName($lastName);
        $runner->setEmail($email);
        $runner->setRaceEditionId($raceEditionId);
        $runner->setBibNumber($reference);
        $runner->setClub($club);
        $runner->setGender($gender);
        $runner->setCategory($category);

        $this->entityManager->persist($runner);
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
