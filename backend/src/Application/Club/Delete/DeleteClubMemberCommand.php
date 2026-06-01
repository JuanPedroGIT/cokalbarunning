<?php

declare(strict_types=1);

namespace App\Application\Club\Delete;

final readonly class DeleteClubMemberCommand
{
    public function __construct(public string $id) {}
}
