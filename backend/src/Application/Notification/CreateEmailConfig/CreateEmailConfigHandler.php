<?php

declare(strict_types=1);

namespace App\Application\Notification\CreateEmailConfig;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Notification\Entity\EmailConfig;
use App\Domain\Notification\Repository\EmailConfigRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateEmailConfigHandler
{
    public function __construct(
        private EmailConfigRepositoryInterface $emailConfigRepository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(CreateEmailConfigCommand $command): array
    {
        $editionId = $command->editionId;
        $type = $command->type;
        $data = $command->data;

        $existing = $this->emailConfigRepository->findByRaceEditionIdAndType($editionId, $type);
        if ($existing !== null) {
            throw new \RuntimeException('Configuration already exists for this edition. Use PUT to update.');
        }

        $config = new EmailConfig(
            id: Uuid::uuid4()->toString(),
            raceEditionId: $editionId,
            type: $type,
        );
        $config->update($data);
        $this->emailConfigRepository->save($config);

        $responseData = $config->toArray();
        if (!empty($responseData['prizeImageUrl'])) {
            $responseData['prizeImageUrl'] = $this->storage->url($responseData['prizeImageUrl']);
        }

        return $responseData;
    }
}
