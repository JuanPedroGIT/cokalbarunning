<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/auth')]
class AuthController extends AbstractController
{
    #[Route('/login', methods: ['POST'])]
    public function login()
    {
        // Handled by lexik_jwt_authentication security layer
    }
}
