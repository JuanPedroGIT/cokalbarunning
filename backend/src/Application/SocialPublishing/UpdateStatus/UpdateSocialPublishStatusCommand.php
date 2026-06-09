<?php

declare(strict_types=1);

namespace App\Application\SocialPublishing\UpdateStatus;

final class UpdateSocialPublishStatusCommand
{
    public function __construct(
        public string $logId,
        public string $status,
        public ?string $externalUrl = null,
    ) {
    }
}
