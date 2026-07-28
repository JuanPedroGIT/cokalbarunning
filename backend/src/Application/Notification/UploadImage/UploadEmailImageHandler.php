<?php

declare(strict_types=1);

namespace App\Application\Notification\UploadImage;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Service\PathGenerator;
use App\Domain\Notification\Entity\EmailConfig;
use App\Domain\Notification\Repository\EmailConfigRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UploadEmailImageHandler
{
    public function __construct(
        private EmailConfigRepositoryInterface $emailConfigRepository,
        private StoragePort $storage,
        private PathGenerator $pathGenerator,
    ) {
    }

    public function __invoke(UploadEmailImageCommand $command): array
    {
        $config = $this->emailConfigRepository->findByRaceEditionIdAndType($command->editionId, $command->type);
        $isNew = false;
        if ($config === null) {
            $config = new EmailConfig(
                id: Uuid::uuid4()->toString(),
                raceEditionId: $command->editionId,
                type: $command->type,
            );
            $isNew = true;
        }

        $file = new UploadedFile(
            $command->tmpPath,
            $command->originalName,
            $command->mimeType,
            null,
            true
        );

        $ext = $file->guessExtension() ?: 'png';
        $path = $this->pathGenerator->emailImagePath($command->type, $ext);

        $previousImageUrl = $config->prizeImageUrl();
        if ($previousImageUrl !== null && $previousImageUrl !== '') {
            $this->storage->delete($previousImageUrl);
        }

        $this->storage->store($file, $path);
        $config->setPrizeImageUrl($path);
        $this->emailConfigRepository->save($config);

        return [
            'id' => $config->id(),
            'prizeImageUrl' => $this->storage->url($path),
            'config' => $config->toArray(),
        ];
    }
}
