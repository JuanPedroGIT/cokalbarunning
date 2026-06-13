<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api\Admin;

use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Domain\Results\Entity\Result as DomainResult;
use App\Domain\Results\Repository\ResultRepositoryInterface;
use App\Domain\Results\ValueObject\Position;
use App\Entity\Category;
use App\Entity\RaceEdition;
use App\Entity\Result;
use App\Entity\Runner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Ramsey\Uuid\Uuid;

#[Route('/api/v1/admin')]
class AdminResultController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private RaceEditionRepositoryInterface $raceEditionRepository,
        private ResultRepositoryInterface $resultRepository,
    ) {
    }

    #[Route('/editions/{id}/results/import', methods: ['POST'])]
    public function import(string $id, Request $request): JsonResponse
    {
        return $this->json(['error' => 'La importacion de resultados esta temporalmente deshabilitada.'], 403);

        // La logica siguiente esta deshabilitada temporalmente
        $edition = $this->raceEditionRepository->findById(RaceEditionId::fromString($id));
        if (!$edition) {
            return $this->json(['error' => 'Edition not found'], 404);
        }

        $file = $request->files->get('file');
        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'No CSV file uploaded or invalid'], 400);
        }

        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return $this->json(['error' => 'Cannot read CSV file'], 400);
        }

        $headers = fgetcsv($handle, 0, ';');
        if (!$headers) {
            fclose($handle);
            return $this->json(['error' => 'Empty CSV file'], 400);
        }

        $headerMap = array_map('strtolower', $headers);

        $this->resultRepository->clearPositionsForEdition(RaceEditionId::fromString($id));

        $created = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $line++;
            if (count($row) !== count($headers)) {
                $errors[] = "Line $line: column count mismatch";
                continue;
            }

            $data = array_combine($headerMap, $row);
            if (!$data) {
                $errors[] = "Line $line: cannot parse row";
                continue;
            }

            try {
                $this->importRow($id, $data);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Line $line: " . $e->getMessage();
            }
        }

        fclose($handle);

        if ($created > 0) {
            $this->recalculatePositions($id);
        }

        return $this->json([
            'data' => [
                'created' => $created,
                'errors' => $errors,
            ],
        ]);
    }

    private function importRow(string $editionId, array $data): void
    {
        $firstName = trim($data['firstname'] ?? $data['first_name'] ?? $data['nombre'] ?? '');
        $lastName = trim($data['lastname'] ?? $data['last_name'] ?? $data['apellidos'] ?? '');
        $bibNumber = trim($data['bib'] ?? $data['bibnumber'] ?? $data['dorsal'] ?? '');
        $timeStr = trim($data['time'] ?? $data['finishtime'] ?? $data['tiempo'] ?? '');
        $categoryName = trim($data['category'] ?? $data['categoria'] ?? '');
        $gender = trim($data['gender'] ?? $data['sexo'] ?? '');
        $club = trim($data['club'] ?? '');
        $email = trim($data['email'] ?? '');

        if (!$firstName || !$lastName || !$bibNumber || !$timeStr) {
            throw new \InvalidArgumentException('Missing required fields');
        }

        $finishTimeSeconds = $this->parseTime($timeStr);

        $runner = $this->findOrCreateRunner($firstName, $lastName, $email, $club, $gender);

        $ormEdition = $this->em->getReference(RaceEdition::class, $editionId);
        $category = $this->findOrCreateCategory($ormEdition, $categoryName, $gender);

        $existingResult = $this->em->getRepository(Result::class)->findOneBy([
            'raceEdition' => $ormEdition,
            'bibNumber' => $bibNumber,
        ]);

        if ($existingResult) {
            $result = $existingResult;
        } else {
            $result = new Result();
            $result->setId(Uuid::uuid4()->toString());
            $result->setRaceEdition($ormEdition);
            $result->setRunner($runner);
            $result->setBibNumber($bibNumber);
            $this->em->persist($result);
        }

        $result->setCategory($category);
        $result->setFinishTimeSeconds($finishTimeSeconds);
    }

    private function parseTime(string $timeStr): int
    {
        $parts = explode(':', $timeStr);
        if (count($parts) === 3) {
            return ((int) $parts[0]) * 3600 + ((int) $parts[1]) * 60 + (int) $parts[2];
        }
        if (count($parts) === 2) {
            return ((int) $parts[0]) * 60 + (int) $parts[1];
        }
        throw new \InvalidArgumentException("Invalid time format: $timeStr");
    }

    private function findOrCreateRunner(string $firstName, string $lastName, string $email, string $club, string $gender): Runner
    {
        if ($email) {
            $runner = $this->em->getRepository(Runner::class)->findOneBy(['email' => $email]);
            if ($runner) {
                return $runner;
            }
        }

        $runner = new Runner();
        $runner->setId(Uuid::uuid4()->toString());
        $runner->setFirstName($firstName);
        $runner->setLastName($lastName);
        $runner->setEmail($email ?: null);
        $runner->setClub($club ?: null);
        $runner->setGender($gender ?: null);
        $this->em->persist($runner);

        return $runner;
    }

    private function findOrCreateCategory(RaceEdition $edition, string $categoryName, string $gender): Category
    {
        $category = $this->em->getRepository(Category::class)->findOneBy([
            'raceEdition' => $edition,
            'name' => $categoryName,
        ]);

        if ($category) {
            return $category;
        }

        $category = new Category();
        $category->setId(Uuid::uuid4()->toString());
        $category->setRaceEdition($edition);
        $category->setName($categoryName);
        $category->setMinAge(0);
        $category->setMaxAge(99);
        $category->setDistanceKm(0);
        $category->setGender($gender ?: 'M');
        $this->em->persist($category);

        return $category;
    }

    private function recalculatePositions(string $editionId): void
    {
        $results = $this->resultRepository->findByRaceEdition(RaceEditionId::fromString($editionId));

        usort($results, fn (DomainResult $a, DomainResult $b) => $a->finishTime()->seconds() <=> $b->finishTime()->seconds());

        $overall = 1;
        $byGender = ['M' => 1, 'F' => 1];
        $byCategory = [];

        foreach ($results as $result) {
            $result->setPosition(new Position($overall++));

            $gender = $result->runner()->gender() ?? 'M';
            $result->setGenderPosition(new Position($byGender[$gender]++));

            $catKey = $result->category()->id();
            if (!isset($byCategory[$catKey])) {
                $byCategory[$catKey] = 1;
            }
            $result->setCategoryPosition(new Position($byCategory[$catKey]++));
        }

        $this->resultRepository->saveBulk($results);
    }
}
