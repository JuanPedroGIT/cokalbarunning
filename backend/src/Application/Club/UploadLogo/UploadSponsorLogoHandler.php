<?php

declare(strict_types=1);

namespace App\Application\Club\UploadLogo;

use App\Domain\Club\Repository\SponsorRepositoryInterface;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Service\PathGenerator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UploadSponsorLogoHandler
{
    public function __construct(
        private SponsorRepositoryInterface $repository,
        private StoragePort $storage,
        private PathGenerator $pathGen,
    ) {
    }

    public function __invoke(UploadSponsorLogoCommand $command): string
    {
        $sponsor = $this->repository->findById($command->sponsorId);
        if (!$sponsor) {
            throw new \RuntimeException('Sponsor not found');
        }

        $file = new UploadedFile($command->tmpPath, $command->originalName, $command->mimeType, null, true);
        $ext = $file->guessExtension() ?: 'png';
        $filename = $this->pathGen->sponsorLogoPath($ext);

        if ($sponsor->logoUrl() !== null) {
            $this->storage->delete($sponsor->logoUrl());
        }

        $this->storage->store($file, $filename);
        $sponsor->update(
            name: $sponsor->name(),
            logoUrl: $filename,
            website: $sponsor->website(),
            tier: $sponsor->tier(),
            sortOrder: $sponsor->sortOrder(),
            message: $sponsor->message(),
        );
        $this->repository->save($sponsor);

        return $this->storage->url($filename);
    }
}
