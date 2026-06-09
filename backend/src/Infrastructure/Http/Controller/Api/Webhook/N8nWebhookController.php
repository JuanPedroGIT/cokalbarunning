<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api\Webhook;

use App\Application\SocialPublishing\UpdateStatus\UpdateSocialPublishStatusCommand;
use App\Domain\SocialPublishing\Exception\SocialPublishingException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/webhook/n8n')]
class N8nWebhookController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private string $callbackToken,
    ) {
    }

    #[Route('/update-status-publish', methods: ['POST'])]
    public function updateStatusPublish(Request $request): JsonResponse
    {
        $token = $request->headers->get('X-N8N-Callback-Token');
        if ($token !== $this->callbackToken) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data) || empty($data['logId']) || empty($data['status'])) {
            return $this->json(['error' => 'Invalid payload. Required: logId, status'], 400);
        }

        $command = new UpdateSocialPublishStatusCommand(
            logId: $data['logId'],
            status: $data['status'],
            externalUrl: $data['externalUrl'] ?? null,
        );

        try {
            $this->commandBus->dispatch($command);
        } catch (SocialPublishingException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }

        return $this->json(['data' => ['updated' => true]]);
    }
}
