<?php

declare(strict_types=1);

namespace App\Application\Media\Delete;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Repository\PhotoRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeletePhotoHandler
{
    public function __construct(
        private PhotoRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(DeletePhotoCommand $command): void
    {
        $photo = $this->repository->findById($command->id);
        if (!$photo) {
            throw new \InvalidArgumentException('Photo not found');
        }

        // Delete files from storage
        $this->storage->delete($photo->originalPath());
        if ($photo->thumbPath() !== null) {
            $this->storage->delete($photo->thumbPath());
        }

        $this->repository->remove($photo);
    }
}
