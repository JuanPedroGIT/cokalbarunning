<?php

declare(strict_types=1);

namespace App\Application\Race\Create;

use App\Domain\Race\Entity\RaceEdition;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\EditionYear;
use App\Domain\Race\ValueObject\RaceEditionId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateRaceEditionHandler
{
    public function __construct(
        private RaceEditionRepositoryInterface $repository,
    ) {
    }

    public function __invoke(CreateRaceEditionCommand $command): string
    {
        $edition = new RaceEdition(
            id: RaceEditionId::generate(),
            year: EditionYear::fromInt($command->year),
            name: $command->name,
            description: $command->description,
            date: new \DateTimeImmutable($command->date),
            location: $command->location,
            isActive: $command->isActive,
            posterUrl: $command->posterUrl,
            registrationUrl: $command->registrationUrl,
            shirtUrl: $command->shirtUrl,
            trophyUrl: $command->trophyUrl,
        );

        $this->repository->save($edition);

        return $edition->id()->value();
    }
}
