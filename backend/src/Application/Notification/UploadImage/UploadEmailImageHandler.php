<?php

declare(strict_types=1);

namespace App\Application\Notification\UploadImage;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Service\PathGenerator;
use App\Entity\EmailConfig;
use App\Repository\EmailConfigRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UploadEmailImageHandler
{
    public function __construct(
        private EmailConfigRepository $emailConfigRepository,
        private StoragePort $storage,
        private PathGenerator $pathGenerator,
    ) {
    }

    public function __invoke(UploadEmailImageCommand $command): array
    {
        $config = $this->emailConfigRepository->findByRaceEditionIdAndType($command->editionId, $command->type);
        if ($config === null) {
            $config = new EmailConfig();
            $config->setId(Uuid::uuid4()->toString());
            $config->setRaceEditionId($command->editionId);
            $config->setType($command->type);
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

        $previousImageUrl = $config->getPrizeImageUrl();
        if ($previousImageUrl !== null && $previousImageUrl !== '') {
            $this->storage->delete($previousImageUrl);
        }

        $this->storage->store($file, $path);
        $config->setPrizeImageUrl($path);
        $config->touch();
        $this->emailConfigRepository->save($config);

        return [
            'id' => $config->getId(),
            'prizeImageUrl' => $this->storage->url($path),
            'config' => $config->toArray(),
        ];
    }
}
