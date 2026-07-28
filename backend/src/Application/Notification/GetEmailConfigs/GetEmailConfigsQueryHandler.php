<?php

declare(strict_types=1);

namespace App\Application\Notification\GetEmailConfigs;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Notification\Repository\EmailConfigRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetEmailConfigsQueryHandler
{
    public function __construct(
        private EmailConfigRepositoryInterface $emailConfigRepository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(GetEmailConfigsQuery $query): array
    {
        $configs = $query->editionId !== null
            ? $this->emailConfigRepository->findByRaceEditionId($query->editionId)
            : $this->emailConfigRepository->findAll();

        return array_map(function ($config) {
            $data = $config->toArray();
            if (!empty($data['prizeImageUrl'])) {
                $data['prizeImageUrl'] = $this->storage->url($data['prizeImageUrl']);
            }

            return $data;
        }, $configs);
    }
}
