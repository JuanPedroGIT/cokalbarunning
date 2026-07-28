<?php

declare(strict_types=1);

namespace App\Application\Notification\SendCampaign;

final readonly class SendEmailCampaignCommand
{
    /**
     * @param list<string> $runnerIds
     * @param list<string> $runnerEmails  Fallback: busca runners por email en la edición si no se encuentra por ID
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $emailConfigId,
        public array $runnerIds,
        public string $editionId,
        public array $runnerEmails = [],
        public array $metadata = [],
        public bool $force = false,
        public ?string $sentBy = null,
    ) {
    }
}
