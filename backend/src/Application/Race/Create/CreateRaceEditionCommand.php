<?php

declare(strict_types=1);

namespace App\Application\Race\Create;

final class CreateRaceEditionCommand
{
    public function __construct(
        public int $year,
        public string $name,
        public string $description,
        public string $date,
        public string $location,
        public bool $isActive = true,
        public bool $showBibSearch = false,
        public ?string $posterUrl = null,
        public ?string $registrationUrl = null,
        public ?string $shirtUrl = null,
        public ?string $trophyUrl = null,
    ) {
    }
}
