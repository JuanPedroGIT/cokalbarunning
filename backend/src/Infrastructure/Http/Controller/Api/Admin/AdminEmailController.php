<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api\Admin;

use App\Application\Notification\Command\CreateEmailSendLogCommand;
use App\Application\Notification\CreateEmailConfig\CreateEmailConfigCommand;
use App\Application\Notification\GetEmailConfig\GetEmailConfigQuery;
use App\Application\Notification\GetGenericRecipients\GetGenericRecipientsQuery;
use App\Application\Notification\GetSentCounts\GetEmailSentCountsQuery;
use App\Application\Notification\PreviewRecipients\PreviewEmailRecipientsCommand;
use App\Application\Notification\Query\GetEmailSendLogsQuery;
use App\Application\Notification\Response\EmailSendLogResponseDto;
use App\Application\Notification\SendCampaign\SendEmailCampaignCommand;
use App\Application\Notification\UpdateEmailConfig\UpdateEmailConfigCommand;
use App\Application\Notification\UploadImage\UploadEmailImageCommand;
use App\Domain\Notification\ValueObject\EmailType;
use App\Domain\Notification\Repository\EmailSendLogRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/admin')]
class AdminEmailController extends AbstractController
{
    private const VALID_TYPES = [EmailType::BIB, EmailType::RAFFLE, EmailType::LAST_INSTRUCTIONS, EmailType::THANKS, EmailType::GENERIC];

    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
        private EmailSendLogRepositoryInterface $emailSendLogRepository,
    ) {
    }

    #[Route('/emails/{type}', methods: ['GET'], requirements: ['type' => 'bib|raffle|last_instructions|thanks|generic'])]
    public function list(Request $request, string $type): JsonResponse
    {
        if (!$this->isValidType($type)) {
            return $this->json(['error' => 'Invalid email type'], 400);
        }

        $raceEditionId = $request->query->get('editionId');
        $envelope = $this->queryBus->dispatch(new GetEmailSendLogsQuery(
            type: $type,
            raceEditionId: \is_string($raceEditionId) ? $raceEditionId : null,
        ));

        /** @var EmailSendLogResponseDto[] $dtos */
        $dtos = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json(['data' => array_map(fn ($dto) => $dto->toArray(), $dtos)]);
    }

    #[Route('/emails/{type}/preview', methods: ['POST'], requirements: ['type' => 'bib|raffle|last_instructions|thanks|generic'])]
    public function preview(Request $request, string $type): JsonResponse
    {
        if (!$this->isValidType($type)) {
            return $this->json(['error' => 'Invalid email type'], 400);
        }

        $file = $request->files->get('file');
        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile || !$file->isValid()) {
            return $this->json(['error' => 'No CSV file uploaded or invalid'], 400);
        }

        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            return $this->json(['error' => 'Cannot read CSV file'], 400);
        }

        $editionId = $request->request->get('editionId') ?: null;

        try {
            $envelope = $this->commandBus->dispatch(new PreviewEmailRecipientsCommand(
                type: $type,
                csvContent: $content,
                editionId: \is_string($editionId) && $editionId !== '' ? $editionId : null,
            ));
            $result = $envelope->last(HandledStamp::class)?->getResult();

            return $this->json(['data' => $result]);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[Route('/emails/{type}/send', methods: ['POST'], requirements: ['type' => 'bib|raffle|last_instructions|thanks|generic'])]
    public function send(Request $request, string $type): JsonResponse
    {
        if (!$this->isValidType($type)) {
            return $this->json(['error' => 'Invalid email type'], 400);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $items = $data['items'] ?? [];
        $force = (bool) ($data['force'] ?? false);
        $metadata = \is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
        $editionId = ($data['editionId'] ?? null) ?: null;

        if (!\is_array($items) || $items === []) {
            return $this->json(['error' => 'No recipients provided'], 400);
        }

        $user = $this->getUser();
        $sentBy = $user instanceof \App\Entity\User ? $user->getId() : null;

        $envelope = $this->commandBus->dispatch(new SendEmailCampaignCommand(
            type: $type,
            items: $items,
            editionId: \is_string($editionId) && $editionId !== '' ? $editionId : null,
            metadata: $metadata,
            force: $force,
            sentBy: $sentBy,
        ));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json(['data' => $result]);
    }

    #[Route('/emails/{type}/sent-counts', methods: ['GET'], requirements: ['type' => 'bib|raffle|last_instructions|thanks|generic'])]
    public function sentCounts(Request $request, string $type): JsonResponse
    {
        if (!$this->isValidType($type)) {
            return $this->json(['error' => 'Invalid email type'], 400);
        }

        $raceEditionId = $request->query->get('editionId');
        $envelope = $this->queryBus->dispatch(new GetEmailSentCountsQuery(
            type: $type,
            raceEditionId: \is_string($raceEditionId) && $raceEditionId !== '' ? $raceEditionId : null,
        ));
        $data = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json(['data' => $data]);
    }

    #[Route('/emails/generic/recipients', methods: ['GET'])]
    public function genericRecipients(Request $request): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetGenericRecipientsQuery());
        $data = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json(['data' => $data]);
    }

    #[Route('/emails/{type}/run', methods: ['POST'], requirements: ['type' => 'bib|raffle|last_instructions|thanks|generic'])]
    public function run(Request $request, string $type): JsonResponse
    {
        if (!$this->isValidType($type)) {
            return $this->json(['error' => 'Invalid email type'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $editionId = \is_array($data) ? ($data['editionId'] ?? null) : null;
        $bccEmail = \is_array($data) ? ($data['bccEmail'] ?? null) : null;

        if (\is_string($bccEmail) && $bccEmail !== '') {
            $pendingLogs = $this->emailSendLogRepository->findByTypeAndRaceEditionId($type, $editionId);
            foreach ($pendingLogs as $log) {
                if ($log->status()->isPending()) {
                    $currentMeta = $log->metadata();
                    $currentMeta['bccEmail'] = $bccEmail;
                    $log->setMetadata($currentMeta);
                    $this->emailSendLogRepository->save($log);
                }
            }
        }

        $editionOption = '';
        if ($editionId !== null && $editionId !== '') {
            $editionOption = sprintf(' --edition-id=%s', escapeshellarg((string) $editionId));
        }

        $user = $this->getUser();
        $userOption = '';
        if ($user instanceof \App\Entity\User) {
            $userOption = sprintf(' --user-id=%s', escapeshellarg((string) $user->getId()));
        }

        $command = sprintf(
            'cd /var/www/backend && nohup php bin/console app:emails:send --type=%s%s%s > /var/www/backend/var/log/emails-runner-%s.log 2>&1 &',
            escapeshellarg($type),
            $editionOption,
            $userOption,
            $type
        );

        exec($command);

        return $this->json([
            'data' => [
                'started' => true,
                'message' => 'Envio de emails iniciado en segundo plano.',
            ],
        ]);
    }

    #[Route('/emails/{type}/config', methods: ['GET'], requirements: ['type' => 'raffle|last_instructions|thanks|generic'])]
    public function getConfig(Request $request, string $type): JsonResponse
    {
        $editionId = $request->query->get('editionId');
        if (!\is_string($editionId) || $editionId === '') {
            return $this->json(['error' => 'Edition ID is required'], 400);
        }

        $envelope = $this->queryBus->dispatch(new GetEmailConfigQuery(
            editionId: $editionId,
            type: $type,
        ));
        $data = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json(['data' => $data]);
    }

    #[Route('/emails/{type}/config', methods: ['POST'], requirements: ['type' => 'raffle|last_instructions|thanks|generic'])]
    public function createConfig(Request $request, string $type): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $editionId = $data['editionId'] ?? null;
        if (!\is_string($editionId) || $editionId === '') {
            return $this->json(['error' => 'Edition ID is required'], 400);
        }

        try {
            $envelope = $this->commandBus->dispatch(new CreateEmailConfigCommand(
                editionId: $editionId,
                type: $type,
                data: $data,
            ));
            $responseData = $envelope->last(HandledStamp::class)?->getResult();

            return $this->json(['data' => $responseData], 201);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 409);
        }
    }

    #[Route('/emails/{type}/prize-image', methods: ['POST'], requirements: ['type' => 'raffle|last_instructions|thanks|generic'])]
    public function uploadPrizeImage(Request $request, string $type): JsonResponse
    {
        $editionId = $request->request->get('editionId');
        if (!\is_string($editionId) || $editionId === '') {
            return $this->json(['error' => 'Edition ID is required'], 400);
        }

        $file = $request->files->get('file');
        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile || !$file->isValid()) {
            return $this->json(['error' => 'No image file uploaded or invalid'], 400);
        }

        $envelope = $this->commandBus->dispatch(new UploadEmailImageCommand(
            editionId: $editionId,
            type: $type,
            tmpPath: $file->getPathname(),
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getMimeType() ?? 'image/png',
        ));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json(['data' => $result]);
    }

    #[Route('/emails/{type}/config/{id}', methods: ['PUT'], requirements: ['type' => 'raffle|last_instructions|thanks|generic'])]
    public function updateConfig(string $type, string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        try {
            $envelope = $this->commandBus->dispatch(new UpdateEmailConfigCommand(
                id: $id,
                data: $data,
            ));
            $responseData = $envelope->last(HandledStamp::class)?->getResult();

            return $this->json(['data' => $responseData]);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    // Legacy routes kept for backward compatibility
    #[Route('/bib-emails', methods: ['GET'])]
    public function legacyList(Request $request): JsonResponse
    {
        return $this->list($request, EmailType::BIB);
    }

    #[Route('/bib-emails/preview', methods: ['POST'])]
    public function legacyPreview(Request $request): JsonResponse
    {
        return $this->preview($request, EmailType::BIB);
    }

    #[Route('/bib-emails/send', methods: ['POST'])]
    public function legacySend(Request $request): JsonResponse
    {
        return $this->send($request, EmailType::BIB);
    }

    #[Route('/bib-emails/sent-counts', methods: ['GET'])]
    public function legacySentCounts(Request $request): JsonResponse
    {
        return $this->sentCounts($request, EmailType::BIB);
    }

    #[Route('/bib-emails/run', methods: ['POST'])]
    public function legacyRun(Request $request): JsonResponse
    {
        return $this->run($request, EmailType::BIB);
    }

    private function isValidType(string $type): bool
    {
        return \in_array($type, self::VALID_TYPES, true);
    }
}
