<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class HealthController extends AbstractController
{
    #[Route('/health', methods: ['GET'])]
    public function check(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok', 'timestamp' => time()]);
    }
}
