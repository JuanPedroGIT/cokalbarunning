<?php

declare(strict_types=1);

namespace App\Application\Notification\GetEmailConfig;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Notification\Repository\EmailConfigRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetEmailConfigQueryHandler
{
    public function __construct(
        private EmailConfigRepositoryInterface $emailConfigRepository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(GetEmailConfigQuery $query): ?array
    {
        $config = $this->emailConfigRepository->findByRaceEditionIdAndType($query->editionId, $query->type);
        if ($config === null) {
            return null;
        }

        $data = $config->toArray();
        if (!empty($data['prizeImageUrl'])) {
            $data['prizeImageUrl'] = $this->storage->url($data['prizeImageUrl']);
        }

        return $data;
    }
}
