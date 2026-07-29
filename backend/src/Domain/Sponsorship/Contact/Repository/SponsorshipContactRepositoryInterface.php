<?php

declare(strict_types=1);

namespace App\Domain\Sponsorship\Contact\Repository;

use App\Domain\Sponsorship\Contact\Entity\SponsorshipContact;

interface SponsorshipContactRepositoryInterface
{
    public function save(SponsorshipContact $contact): void;

    /** @return SponsorshipContact[] */
    public function findAll(): array;
}
