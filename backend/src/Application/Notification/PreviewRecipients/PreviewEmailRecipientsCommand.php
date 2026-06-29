<?php

declare(strict_types=1);

namespace App\Application\Notification\PreviewRecipients;

final readonly class PreviewEmailRecipientsCommand
{
    public function __construct(
        public string $type,
        public string $csvContent,
        public ?string $editionId = null,
    ) {
    }
}
