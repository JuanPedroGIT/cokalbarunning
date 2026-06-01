<?php

declare(strict_types=1);

namespace App\Application\Club\UploadPhoto;

use App\Domain\Club\Repository\ClubMemberRepositoryInterface;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Service\PathGenerator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UploadClubMemberPhotoHandler
{
    public function __construct(
        private ClubMemberRepositoryInterface $repository,
        private StoragePort $storage,
        private PathGenerator $pathGen,
    ) {
    }

    public function __invoke(UploadClubMemberPhotoCommand $command): string
    {
        $member = $this->repository->findById($command->memberId);
        if (!$member) {
            throw new \RuntimeException('Club member not found');
        }

        $file = new UploadedFile($command->tmpPath, $command->originalName, $command->mimeType, null, true);
        $ext = $file->guessExtension() ?: 'jpg';
        $filename = $this->pathGen->clubMemberPhotoPath($ext);

        if ($member->photoPath() !== null) {
            $this->storage->delete($member->photoPath());
        }

        $this->storage->store($file, $filename);
        $member->update(
            name: $member->name(),
            description: $member->description(),
            photoPath: $filename,
            isActive: $member->isActive(),
            sortOrder: $member->sortOrder(),
        );
        $this->repository->save($member);

        return $this->storage->url($filename);
    }
}
