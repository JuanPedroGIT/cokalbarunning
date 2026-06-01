<?php

declare(strict_types=1);

namespace App\Application\Media\Upload;

use App\Domain\Media\Entity\Photo;
use App\Domain\Media\Repository\PhotoRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UploadPhotoHandler
{
    public function __construct(
        private PhotoRepositoryInterface $repository,
    ) {
    }

    public function __invoke(UploadPhotoCommand $command): string
    {
        $photo = new Photo(
            id: Uuid::uuid4()->toString(),
            originalPath: $command->originalPath,
            thumbPath: $command->thumbPath,
            raceEditionId: $command->raceEditionId ? RaceEditionId::fromString($command->raceEditionId) : null,
            altText: $command->altText,
            isFeatured: $command->isFeatured,
            sortOrder: $command->sortOrder,
        );

        $this->repository->save($photo);

        return $photo->id();
    }
}
