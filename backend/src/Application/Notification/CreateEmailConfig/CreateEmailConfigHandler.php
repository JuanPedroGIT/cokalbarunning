<?php

declare(strict_types=1);

namespace App\Application\Notification\CreateEmailConfig;

use App\Domain\Media\Port\StoragePort;
use App\Entity\EmailConfig;
use App\Repository\EmailConfigRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateEmailConfigHandler
{
    public function __construct(
        private EmailConfigRepository $emailConfigRepository,
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

        $config = new EmailConfig();
        $config->setId(Uuid::uuid4()->toString());
        $config->setRaceEditionId($editionId);
        $config->setType($type);
        $this->applyEmailConfigData($config, $data);
        $this->emailConfigRepository->save($config);

        $responseData = $config->toArray();
        if (!empty($responseData['prizeImageUrl'])) {
            $responseData['prizeImageUrl'] = $this->storage->url($responseData['prizeImageUrl']);
        }

        return $responseData;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyEmailConfigData(EmailConfig $config, array $data): void
    {
        if (array_key_exists('subject', $data)) {
            $config->setSubject($data['subject'] !== null && $data['subject'] !== '' ? (string) $data['subject'] : null);
        }
        if (array_key_exists('title', $data)) {
            $config->setTitle($data['title'] !== null && $data['title'] !== '' ? (string) $data['title'] : null);
        }
        if (array_key_exists('description', $data)) {
            $config->setDescription($data['description'] !== null && $data['description'] !== '' ? (string) $data['description'] : null);
        }
        if (array_key_exists('prize', $data)) {
            $config->setPrize($data['prize'] !== null && $data['prize'] !== '' ? (string) $data['prize'] : null);
        }
        if (array_key_exists('drawDate', $data)) {
            $config->setDrawDate($data['drawDate'] !== null && $data['drawDate'] !== '' ? (string) $data['drawDate'] : null);
        }
        if (array_key_exists('prizeImageUrl', $data)) {
            $newValue = $data['prizeImageUrl'] !== null && $data['prizeImageUrl'] !== '' ? (string) $data['prizeImageUrl'] : null;
            if ($newValue !== null && !str_starts_with($newValue, 'http://') && !str_starts_with($newValue, 'https://')) {
                $newValue = ltrim($newValue, '/');
            } elseif ($newValue !== null) {
                $newValue = ltrim(preg_replace('#^https?://[^/]+/#', '', $newValue) ?? $newValue, '/');
            }
            $oldValue = $config->getPrizeImageUrl();
            if ($newValue === null && $oldValue !== null && $oldValue !== '') {
                $this->storage->delete($oldValue);
            }
            $config->setPrizeImageUrl($newValue !== '' ? $newValue : null);
        }
    }
}
