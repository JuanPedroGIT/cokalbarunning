<?php

declare(strict_types=1);

namespace App\Application\Race\DeleteImage;

final readonly class DeleteRaceEditionImageCommand
{
    public function __construct(
        public string $editionId,
        public string $type, // 'poster', 'shirt', 'trophy'
    ) {
    }
}
