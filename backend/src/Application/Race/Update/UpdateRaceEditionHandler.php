<?php

declare(strict_types=1);

namespace App\Application\Race\Update;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\EditionYear;
use App\Domain\Race\ValueObject\RaceEditionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateRaceEditionHandler
{
    public function __construct(
        private RaceEditionRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(UpdateRaceEditionCommand $command): void
    {
        $edition = $this->repository->findById(RaceEditionId::fromString($command->id));
        if (!$edition) {
            throw new \InvalidArgumentException('Edition not found');
        }

        if ($command->name !== null || $command->description !== null || $command->date !== null || $command->location !== null || $command->shirtUrl !== null || $command->trophyUrl !== null || $command->showBibSearch !== null) {
            $edition->update(
                name: $command->name ?? $edition->name(),
                description: $command->description ?? $edition->description(),
                date: $command->date !== null ? new \DateTimeImmutable($command->date) : $edition->date(),
                location: $command->location ?? $edition->location(),
                shirtUrl: $command->shirtUrl !== null ? ($command->shirtUrl ?: null) : $edition->shirtUrl(),
                trophyUrl: $command->trophyUrl !== null ? ($command->trophyUrl ?: null) : $edition->trophyUrl(),
                showBibSearch: $command->showBibSearch,
            );
        }

        if ($command->year !== null) {
            $edition->setYear(EditionYear::fromInt($command->year));
        }

        if ($command->isActive !== null) {
            $command->isActive ? $edition->activate() : $edition->deactivate();
        }

        if ($command->showBibSearch !== null && $command->name === null && $command->description === null && $command->date === null && $command->location === null && $command->shirtUrl === null && $command->trophyUrl === null) {
            $edition->setShowBibSearch($command->showBibSearch);
        }

        if ($command->posterUrl !== null) {
            $edition->setPosterUrl($this->normalizePath($command->posterUrl));
        }

        if ($command->registrationUrl !== null) {
            $edition->setRegistrationUrl($command->registrationUrl ?: null);
        }

        if ($command->shirtUrl !== null) {
            $edition->setShirtUrl($this->normalizePath($command->shirtUrl));
        }

        if ($command->trophyUrl !== null) {
            $edition->setTrophyUrl($this->normalizePath($command->trophyUrl));
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

    private function normalizePath(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        $base = rtrim($this->storage->url(''), '/');
        if (str_starts_with($value, $base)) {
            $normalized = substr($value, strlen($base) + 1);
            return $normalized === '' ? null : $normalized;
        }
        return $value;
    }
}
