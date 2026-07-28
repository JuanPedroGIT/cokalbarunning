<?php

declare(strict_types=1);

namespace App\Application\Notification\UpdateEmailConfig;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Notification\Repository\EmailConfigRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateEmailConfigHandler
{
    public function __construct(
        private EmailConfigRepositoryInterface $emailConfigRepository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(UpdateEmailConfigCommand $command): array
    {
        $data = $command->data;
        $config = $this->emailConfigRepository->findById($command->id);
        if ($config === null) {
            throw new \RuntimeException('Configuration not found');
        }

        $oldImageUrl = $config->prizeImageUrl();
        $config->update($data);
        $newImageUrl = $config->prizeImageUrl();

        if ($newImageUrl === null && $oldImageUrl !== null && $oldImageUrl !== '') {
            $this->storage->delete($oldImageUrl);
        }

        $this->emailConfigRepository->save($config);

        $responseData = $config->toArray();
        if (!empty($responseData['prizeImageUrl'])) {
            $responseData['prizeImageUrl'] = $this->storage->url($responseData['prizeImageUrl']);
        }

        return $responseData;
    }
}
