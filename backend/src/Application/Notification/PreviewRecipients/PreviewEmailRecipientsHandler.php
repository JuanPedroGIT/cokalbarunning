<?php

declare(strict_types=1);

namespace App\Application\Notification\PreviewRecipients;

use App\Application\Race\BibEmail\EmailRecipientDto;
use App\Application\Race\BibEmail\ParseEmailCsv;
use App\Domain\Notification\Repository\EmailSendLogRepositoryInterface;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Entity\Runner;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class PreviewEmailRecipientsHandler
{
    public function __construct(
        private ParseEmailCsv $csvParser,
        private RaceEditionRepositoryInterface $raceEditionRepository,
        private EmailSendLogRepositoryInterface $emailSendLogRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(PreviewEmailRecipientsCommand $command): array
    {
        $edition = null;
        if ($command->editionId !== null) {
            $edition = $this->raceEditionRepository->findById(RaceEditionId::fromString($command->editionId));
            if (!$edition) {
                throw new \RuntimeException('Edition not found');
            }
        } else {
            $edition = $this->raceEditionRepository->findActive();
        }

        $recipients = $this->csvParser->parse($command->csvContent);

        $raceEditionId = $edition?->id()->value();
        $runnersCreated = 0;
        if ($raceEditionId !== null) {
            foreach ($recipients as $recipient) {
                if ($recipient->email === '' || $recipient->emailValid === false) {
                    continue;
                }
                $this->upsertRunner($recipient->toArray(), $raceEditionId);
                $runnersCreated++;
            }
            $this->entityManager->flush();
        }

        $type = $command->type;
        $items = array_map(function (EmailRecipientDto $recipient) use ($type) {
            $existing = $this->emailSendLogRepository->findByEmailTypeAndReference(
                $recipient->email,
                $type,
                $recipient->reference
            );

            return [
                'firstName' => $recipient->firstName,
                'lastName' => $recipient->lastName,
                'fullName' => $recipient->fullName(),
                'email' => $recipient->email,
                'reference' => $recipient->reference,
                'club' => $recipient->club,
                'category' => $recipient->category,
                'shirtSize' => $recipient->shirtSize,
                'emailValid' => $recipient->emailValid,
                'status' => $existing?->status()->value() ?? 'not_sent',
                'errorMessage' => $existing?->errorMessage(),
                'sentAt' => $existing?->sentAt()?->format('Y-m-d H:i:s'),
            ];
        }, $recipients);

        return [
            'edition' => $edition !== null ? [
                'id' => $edition->id()->value(),
                'name' => $edition->name(),
                'year' => $edition->year()->value(),
            ] : null,
            'items' => $items,
            'runnersCreated' => $runnersCreated,
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
        $birthDate = null;
        $rawBirthDate = $item['birthDate'] ?? null;
        if (\is_string($rawBirthDate) && $rawBirthDate !== '') {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $rawBirthDate)
                ?? \DateTimeImmutable::createFromFormat('d_m_Y', $rawBirthDate)
                ?? \DateTimeImmutable::createFromFormat('d-m-Y', $rawBirthDate)
                ?? \DateTimeImmutable::createFromFormat('d/m/Y', $rawBirthDate);
            if ($date !== false) {
                $birthDate = $date->setTime(0, 0, 0);
            }
        }

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
        $runner->setBirthDate($birthDate);

        $this->entityManager->persist($runner);
    }
}
