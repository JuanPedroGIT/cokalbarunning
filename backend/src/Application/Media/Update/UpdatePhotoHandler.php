<?php

declare(strict_types=1);

namespace App\Application\Media\Update;

use App\Domain\Media\Repository\PhotoRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdatePhotoHandler
{
    public function __construct(
        private PhotoRepositoryInterface $repository,
    ) {
    }

    public function __invoke(UpdatePhotoCommand $command): void
    {
        $photo = $this->repository->findById($command->id);
        if (!$photo) {
            throw new \InvalidArgumentException('Photo not found');
        }

        if ($command->altText !== null) {
            $photo->setAltText($command->altText ?: null);
        }
        if ($command->isFeatured !== null) {
            $photo->setFeatured($command->isFeatured);
        }
        if ($command->sortOrder !== null) {
            $photo->setSortOrder($command->sortOrder);
        }
        if ($command->raceEditionId !== null) {
            $photo->setRaceEditionId(
                $command->raceEditionId ? RaceEditionId::fromString($command->raceEditionId) : null
            );
        }

        $this->repository->save($photo);
    }
}
