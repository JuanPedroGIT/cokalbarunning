<?php

declare(strict_types=1);

namespace App\Application\Media\Delete;

final readonly class DeletePhotoCommand
{
    public function __construct(public string $id)
    {
    }
}
