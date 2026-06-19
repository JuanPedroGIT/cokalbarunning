<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Entity\Runner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class RunnerController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/runners', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $editionId = $request->query->get('editionId');
        $name = trim((string) $request->query->get('name'));

        if (!\is_string($editionId) || $editionId === '') {
            return $this->json(['error' => 'editionId is required'], 400);
        }

        if (mb_strlen($name) < 4) {
            return $this->json(['error' => 'name must be at least 4 characters'], 400);
        }

        $qb = $this->entityManager->getRepository(Runner::class)
            ->createQueryBuilder('r')
            ->where('r.raceEditionId = :editionId')
            ->setParameter('editionId', $editionId);

        $term = mb_strtolower($name);
        $qb
            ->andWhere(
                'LOWER(r.firstName) LIKE :name OR LOWER(r.lastName) LIKE :name OR LOWER(CONCAT(r.firstName, \' \', r.lastName)) LIKE :name'
            )
            ->setParameter('name', '%' . $term . '%');

        $runners = $qb
            ->orderBy('r.firstName', 'ASC')
            ->addOrderBy('r.lastName', 'ASC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        $data = array_map(static function (Runner $runner) {
            return [
                'id' => $runner->getId(),
                'firstName' => $runner->getFirstName(),
                'lastName' => $runner->getLastName(),
                'fullName' => trim($runner->getFirstName() . ' ' . $runner->getLastName()),
                'bibNumber' => $runner->getBibNumber(),
            ];
        }, $runners);

        return $this->json(['data' => $data]);
    }
}
