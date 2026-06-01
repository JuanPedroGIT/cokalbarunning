<?php

declare(strict_types=1);

namespace App\Domain\Results\Entity;

use App\Domain\Race\Entity\Category;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Domain\Registration\Entity\Runner;
use App\Domain\Registration\ValueObject\BibNumber;
use App\Domain\Results\ValueObject\FinishTime;
use App\Domain\Results\ValueObject\Position;

final class Result
{
    public function __construct(
        private string $id,
        private RaceEditionId $raceEditionId,
        private Runner $runner,
        private BibNumber $bibNumber,
        private Category $category,
        private FinishTime $finishTime,
        private ?Position $position = null,
        private ?Position $genderPosition = null,
        private ?Position $categoryPosition = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function raceEditionId(): RaceEditionId
    {
        return $this->raceEditionId;
    }

    public function runner(): Runner
    {
        return $this->runner;
    }

    public function bibNumber(): BibNumber
    {
        return $this->bibNumber;
    }

    public function category(): Category
    {
        return $this->category;
    }

    public function finishTime(): FinishTime
    {
        return $this->finishTime;
    }

    public function position(): ?Position
    {
        return $this->position;
    }

    public function genderPosition(): ?Position
    {
        return $this->genderPosition;
    }

    public function categoryPosition(): ?Position
    {
        return $this->categoryPosition;
    }

    public function setPosition(Position $position): void
    {
        $this->position = $position;
    }

    public function setGenderPosition(Position $position): void
    {
        $this->genderPosition = $position;
    }

    public function setCategoryPosition(Position $position): void
    {
        $this->categoryPosition = $position;
    }
}
