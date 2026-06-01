<?php

declare(strict_types=1);

namespace App\Application\Race\Query;

final readonly class GetEditionByYearQuery
{
    public function __construct(public int $year)
    {
    }
}
