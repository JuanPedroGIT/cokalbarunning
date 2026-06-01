<?php

declare(strict_types=1);

namespace App\Application\Race\Update;

final class UpdateRaceEditionCommand
{
    public function __construct(
        public string $id,
        public ?int $year = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $date = null,
        public ?string $location = null,
        public ?bool $isActive = null,
        public ?string $posterUrl = null,
        public ?string $registrationUrl = null,
        public ?string $shirtUrl = null,
        public ?string $inscriptionInfo = null,
        public ?string $solidarityCause = null,
        public ?string $solidarityUrl = null,
    ) {
    }
}
