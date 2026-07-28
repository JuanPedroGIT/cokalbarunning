<?php

declare(strict_types=1);

namespace App\Application\Notification\PreviewEmailTemplate;

final readonly class PreviewEmailTemplateQuery
{
    public function __construct(
        public string $emailConfigId,
    ) {
    }
}
