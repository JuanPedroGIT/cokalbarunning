<?php

declare(strict_types=1);

namespace App\Application\Club\UploadLogo;

final readonly class UploadSponsorLogoCommand
{
    public function __construct(
        public string $sponsorId,
        public string $originalName,
        public string $mimeType,
        public string $tmpPath,
    ) {
    }
}
