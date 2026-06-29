<?php

declare(strict_types=1);

namespace App\Application\Notification\SendCampaign;

final readonly class SendEmailCampaignCommand
{
    /**
     * @param list<array<string, mixed>> $items
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $type,
        public array $items,
        public ?string $editionId = null,
        public array $metadata = [],
        public bool $force = false,
        public ?string $sentBy = null,
    ) {
    }
}
