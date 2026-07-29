<?php

declare(strict_types=1);

namespace App\Application\Sponsorship\SubmitContact;

final readonly class SubmitSponsorshipContactCommand
{
    public function __construct(
        public string $name,
        public string $company,
        public string $email,
        public string $phone,
        public ?string $message = null,
    ) {
    }
}
