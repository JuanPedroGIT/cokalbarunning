<?php

declare(strict_types=1);

namespace App\Application\Race\Update;

use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\EditionYear;
use App\Domain\Race\ValueObject\RaceEditionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateRaceEditionHandler
{
    public function __construct(
        private RaceEditionRepositoryInterface $repository,
    ) {
    }

    public function __invoke(UpdateRaceEditionCommand $command): void
    {
        $edition = $this->repository->findById(RaceEditionId::fromString($command->id));
        if (!$edition) {
            throw new \InvalidArgumentException('Edition not found');
        }

        if ($command->name !== null || $command->description !== null || $command->date !== null || $command->location !== null || $command->shirtUrl !== null) {
            $edition->update(
                name: $command->name ?? $edition->name(),
                description: $command->description ?? $edition->description(),
                date: $command->date !== null ? new \DateTimeImmutable($command->date) : $edition->date(),
                location: $command->location ?? $edition->location(),
                shirtUrl: $command->shirtUrl !== null ? ($command->shirtUrl ?: null) : $edition->shirtUrl(),
            );
        }

        if ($command->year !== null) {
            $edition->setYear(EditionYear::fromInt($command->year));
        }

        if ($command->isActive !== null) {
            $command->isActive ? $edition->activate() : $edition->deactivate();
        }

        if ($command->posterUrl !== null) {
            $edition->setPosterUrl($command->posterUrl ?: null);
        }

        if ($command->registrationUrl !== null) {
            $edition->setRegistrationUrl($command->registrationUrl ?: null);
        }

        if ($command->shirtUrl !== null) {
            $edition->setShirtUrl($command->shirtUrl ?: null);
        }

        if ($command->inscriptionInfo !== null) {
            $edition->setInscriptionInfo($command->inscriptionInfo ?: null);
        }
        if ($command->solidarityCause !== null) {
            $edition->setSolidarityCause($command->solidarityCause ?: null);
        }
        if ($command->solidarityUrl !== null) {
            $edition->setSolidarityUrl($command->solidarityUrl ?: null);
        }

        $this->repository->save($edition);
    }
}
