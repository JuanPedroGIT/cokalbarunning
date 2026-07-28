<?php

declare(strict_types=1);

namespace App\Application\Notification\PreviewRecipients;

final readonly class PreviewEmailRecipientsCommand
{
    public function __construct(
        public string $emailConfigId,
        public ?string $bibFrom = null,
        public ?string $bibTo = null,
    ) {
    }
}
