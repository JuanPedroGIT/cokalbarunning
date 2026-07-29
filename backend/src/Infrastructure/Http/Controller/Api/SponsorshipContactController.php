<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Application\Sponsorship\GetContacts\GetSponsorshipContactsQuery;
use App\Application\Sponsorship\SubmitContact\SubmitSponsorshipContactCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class SponsorshipContactController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
    ) {
    }

    #[Route('/admin/sponsorship/contacts', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetSponsorshipContactsQuery());
        $data = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json(['data' => $data]);
    }

    #[Route('/sponsorship/contact', methods: ['POST'])]
    public function submit(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $name = trim((string) ($data['name'] ?? ''));
        $company = trim((string) ($data['company'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $message = isset($data['message']) ? trim((string) $data['message']) : null;

        if ($name === '' || $company === '' || $email === '' || $phone === '') {
            return $this->json(['error' => 'Nombre, empresa, email y teléfono son obligatorios'], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Email no válido'], 422);
        }

        $envelope = $this->commandBus->dispatch(new SubmitSponsorshipContactCommand(
            name: $name,
            company: $company,
            email: $email,
            phone: $phone,
            message: $message !== '' ? $message : null,
        ));

        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json(['data' => $result], 201);
    }
}
