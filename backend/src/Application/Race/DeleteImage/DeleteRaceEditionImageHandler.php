<?php

declare(strict_types=1);

namespace App\Application\Race\DeleteImage;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteRaceEditionImageHandler
{
    public function __construct(
        private RaceEditionRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(DeleteRaceEditionImageCommand $command): void
    {
        $edition = $this->repository->findById(RaceEditionId::fromString($command->editionId));
        if (!$edition) {
            throw new \RuntimeException('Edition not found');
        }

        $url = match ($command->type) {
            'poster' => $edition->posterUrl(),
            'shirt' => $edition->shirtUrl(),
            'trophy' => $edition->trophyUrl(),
            default => throw new \InvalidArgumentException('Invalid image type: ' . $command->type),
        };

        if ($url !== null) {
            $this->storage->delete($url);
        }

        match ($command->type) {
            'poster' => $edition->setPosterUrl(null),
            'shirt' => $edition->setShirtUrl(null),
            'trophy' => $edition->setTrophyUrl(null),
        };

        $this->repository->save($edition);
    }
}
