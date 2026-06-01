<?php

declare(strict_types=1);

namespace App\Application\Club\Delete;

use App\Domain\Club\Repository\SponsorRepositoryInterface;
use App\Domain\Media\Port\StoragePort;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteSponsorHandler
{
    public function __construct(
        private SponsorRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(DeleteSponsorCommand $command): void
    {
        $sponsor = $this->repository->findById($command->id);
        if (!$sponsor) {
            throw new \InvalidArgumentException('Sponsor not found');
        }

        // Delete logo from storage if exists
        $logoUrl = $sponsor->logoUrl();
        if ($logoUrl !== null) {
            $this->storage->delete($logoUrl);
        }

        $this->repository->remove($sponsor);
    }
}
