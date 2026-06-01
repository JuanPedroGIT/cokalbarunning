<?php

declare(strict_types=1);

namespace App\Application\Club\Create;

use App\Domain\Club\Entity\Sponsor;
use App\Domain\Club\Repository\SponsorRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateSponsorHandler
{
    public function __construct(
        private SponsorRepositoryInterface $repository,
    ) {
    }

    public function __invoke(CreateSponsorCommand $command): string
    {
        $sponsor = new Sponsor(
            id: Uuid::uuid4()->toString(),
            name: $command->name,
            logoUrl: $command->logoUrl,
            website: $command->website,
            tier: $command->tier,
            isActive: $command->isActive,
            sortOrder: $command->sortOrder,
            message: $command->message,
        );

        $this->repository->save($sponsor);

        return $sponsor->id();
    }
}
