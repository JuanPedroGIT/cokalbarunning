<?php

declare(strict_types=1);

namespace App\Application\Results\Query;

final readonly class GetResultsByYearQuery
{
    public function __construct(public int $year)
    {
    }
}
