<?php

declare(strict_types=1);

namespace App\Application\Race\Delete;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteRaceEditionHandler
{
    public function __construct(
        private RaceEditionRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(DeleteRaceEditionCommand $command): void
    {
        $edition = $this->repository->findById(RaceEditionId::fromString($command->id));
        if (!$edition) {
            throw new \InvalidArgumentException('Edition not found');
        }

        // Delete associated files from storage
        $posterUrl = $edition->posterUrl();
        if ($posterUrl !== null) {
            $this->storage->delete($posterUrl);
        }

        $shirtUrl = $edition->shirtUrl();
        if ($shirtUrl !== null) {
            $this->storage->delete($shirtUrl);
        }

        $trophyUrl = $edition->trophyUrl();
        if ($trophyUrl !== null) {
            $this->storage->delete($trophyUrl);
        }

        $this->repository->remove($edition);
    }
}
