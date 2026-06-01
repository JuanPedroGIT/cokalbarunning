<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ResultRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResultRepository::class)]
#[ORM\Table(name: 'results')]
#[ORM\Index(columns: ['race_edition_id'], name: 'idx_result_edition')]
class Result
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: RaceEdition::class)]
    #[ORM\JoinColumn(nullable: false)]
    private RaceEdition $raceEdition;

    #[ORM\ManyToOne(targetEntity: Runner::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Runner $runner;

    #[ORM\Column(length: 20)]
    private string $bibNumber;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\Column]
    private int $finishTimeSeconds;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\Column(nullable: true)]
    private ?int $genderPosition = null;

    #[ORM\Column(nullable: true)]
    private ?int $categoryPosition = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getRaceEdition(): RaceEdition
    {
        return $this->raceEdition;
    }

    public function setRaceEdition(RaceEdition $raceEdition): static
    {
        $this->raceEdition = $raceEdition;
        return $this;
    }

    public function getRunner(): Runner
    {
        return $this->runner;
    }

    public function setRunner(Runner $runner): static
    {
        $this->runner = $runner;
        return $this;
    }

    public function getBibNumber(): string
    {
        return $this->bibNumber;
    }

    public function setBibNumber(string $bibNumber): static
    {
        $this->bibNumber = $bibNumber;
        return $this;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getFinishTimeSeconds(): int
    {
        return $this->finishTimeSeconds;
    }

    public function setFinishTimeSeconds(int $finishTimeSeconds): static
    {
        $this->finishTimeSeconds = $finishTimeSeconds;
        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getGenderPosition(): ?int
    {
        return $this->genderPosition;
    }

    public function setGenderPosition(?int $genderPosition): static
    {
        $this->genderPosition = $genderPosition;
        return $this;
    }

    public function getCategoryPosition(): ?int
    {
        return $this->categoryPosition;
    }

    public function setCategoryPosition(?int $categoryPosition): static
    {
        $this->categoryPosition = $categoryPosition;
        return $this;
    }
}
