<?php

declare(strict_types=1);

namespace App\Application\Club\Create;

use App\Domain\Club\Entity\ClubMember;
use App\Domain\Club\Repository\ClubMemberRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateClubMemberHandler
{
    public function __construct(
        private ClubMemberRepositoryInterface $repository,
    ) {
    }

    public function __invoke(CreateClubMemberCommand $command): string
    {
        $id = Uuid::uuid4()->toString();
        $member = new ClubMember(
            id: $id,
            name: $command->name,
            description: $command->description,
            photoPath: $command->photoPath,
            isActive: $command->isActive,
            sortOrder: $command->sortOrder,
        );

        $this->repository->save($member);

        return $id;
    }
}
