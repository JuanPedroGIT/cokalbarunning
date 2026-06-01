<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Race\ValueObject\RaceEditionId;
use App\Domain\Registration\ValueObject\BibNumber;
use App\Domain\Results\Entity\Result as DomainResult;
use App\Domain\Results\ValueObject\FinishTime;
use App\Domain\Results\ValueObject\Position;
use App\Entity\Result as OrmResult;

final class ResultMapper
{
    public function __construct(
        private RunnerMapper $runnerMapper,
        private CategoryMapper $categoryMapper,
    ) {
    }

    public function toDomain(OrmResult $orm): DomainResult
    {
        return new DomainResult(
            id: $orm->getId(),
            raceEditionId: RaceEditionId::fromString($orm->getRaceEdition()->getId()),
            runner: $this->runnerMapper->toDomain($orm->getRunner()),
            bibNumber: BibNumber::fromString($orm->getBibNumber()),
            category: $this->categoryMapper->toDomain($orm->getCategory()),
            finishTime: FinishTime::fromSeconds($orm->getFinishTimeSeconds()),
            position: $orm->getPosition() !== null ? Position::fromInt($orm->getPosition()) : null,
            genderPosition: $orm->getGenderPosition() !== null ? Position::fromInt($orm->getGenderPosition()) : null,
            categoryPosition: $orm->getCategoryPosition() !== null ? Position::fromInt($orm->getCategoryPosition()) : null,
        );
    }

    public function toOrm(DomainResult $domain, ?OrmResult $orm = null): OrmResult
    {
        $target = $orm ?? new OrmResult();
        $target->setId($domain->id());
        $target->setBibNumber($domain->bibNumber()->value());
        $target->setFinishTimeSeconds($domain->finishTime()->seconds());
        $target->setPosition($domain->position()?->value());
        $target->setGenderPosition($domain->genderPosition()?->value());
        $target->setCategoryPosition($domain->categoryPosition()?->value());

        return $target;
    }
}
