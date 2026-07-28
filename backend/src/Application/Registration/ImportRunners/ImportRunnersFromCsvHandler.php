<?php

declare(strict_types=1);

namespace App\Application\Registration\ImportRunners;

use App\Application\Race\BibEmail\ParseEmailCsv;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Domain\Registration\Entity\Runner;
use App\Domain\Registration\Repository\RunnerRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ImportRunnersFromCsvHandler
{
    public function __construct(
        private ParseEmailCsv $csvParser,
        private RunnerRepositoryInterface $runnerRepository,
        private RaceEditionRepositoryInterface $raceEditionRepository,
    ) {
    }

    public function __invoke(ImportRunnersFromCsvCommand $command): ImportRunnersResultDto
    {
        $edition = $this->raceEditionRepository->findById(RaceEditionId::fromString($command->raceEditionId));
        if ($edition === null) {
            throw new \RuntimeException('Edition not found');
        }

        $raceEditionId = $edition->id()->value();
        $recipients = $this->csvParser->parse($command->csvContent);

        $created = 0;
        $skipped = 0;

        foreach ($recipients as $recipient) {
            $email = trim($recipient->email);

            if ($email === '' || $recipient->emailValid === false) {
                $skipped++;
                continue;
            }

            $firstName = trim($recipient->firstName);
            $lastName = trim($recipient->lastName);

            if ($firstName === '') {
                $fullName = $recipient->fullName();
                if ($fullName !== '' && $fullName !== $email) {
                    $parts = explode(' ', $fullName, 2);
                    $firstName = $parts[0];
                    $lastName = $parts[1] ?? '';
                }
            }

            if ($firstName === '') {
                $skipped++;
                continue;
            }

            $reference = $recipient->reference;
            $isRealBib = $reference !== null && $reference !== '' && trim($reference, '0') !== '';

            // El dorsal es el identificador único. Solo se comprueba duplicado si es un dorsal real.
            if ($isRealBib) {
                $existingByBib = $this->runnerRepository->findByBibNumberAndEditionId($reference, $raceEditionId);
                if ($existingByBib !== null) {
                    $skipped++;
                    continue;
                }
            }

            // Dorsales 0, 00, 000 o vacíos: se insertan siempre sin comprobación
            $runner = new Runner(
                id: Uuid::uuid4()->toString(),
                firstName: $firstName,
                lastName: $lastName,
                email: $email,
                raceEditionId: $raceEditionId,
                bibNumber: $reference,
                club: $recipient->club,
                birthDate: $recipient->birthDate,
                gender: $recipient->gender,
                category: $recipient->category,
            );
            $this->runnerRepository->save($runner);
            $created++;
        }

        return new ImportRunnersResultDto(
            created: $created,
            updated: 0, // Ya no se actualiza: el dorsal manda
            skipped: $skipped,
            total: $created + $skipped, // Total de filas procesadas del CSV
        );
    }
}
