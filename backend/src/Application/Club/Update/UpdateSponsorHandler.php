<?php

declare(strict_types=1);

namespace App\Application\Club\Update;

use App\Domain\Club\Repository\SponsorRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateSponsorHandler
{
    public function __construct(
        private SponsorRepositoryInterface $repository,
    ) {
    }

    public function __invoke(UpdateSponsorCommand $command): void
    {
        $sponsor = $this->repository->findById($command->id);
        if (!$sponsor) {
            throw new \InvalidArgumentException('Sponsor not found');
        }

        $sponsor->update(
            name: $command->name ?? $sponsor->name(),
            logoUrl: $command->logoUrl !== null ? ($command->logoUrl ?: null) : $sponsor->logoUrl(),
            website: $command->website !== null ? ($command->website ?: null) : $sponsor->website(),
            tier: $command->tier ?? $sponsor->tier(),
            sortOrder: $command->sortOrder ?? $sponsor->sortOrder(),
            message: $command->message !== null ? ($command->message ?: null) : $sponsor->message(),
        );

        if ($command->isActive !== null) {
            $command->isActive ? $sponsor->activate() : $sponsor->deactivate();
        }

        $this->repository->save($sponsor);
    }
}
