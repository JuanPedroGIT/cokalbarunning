<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api\Admin;

use App\Application\Notification\Command\CreateEmailSendLogCommand;
use App\Application\Notification\Query\GetEmailSendLogsQuery;
use App\Application\Notification\Response\EmailSendLogResponseDto;
use App\Application\Race\BibEmail\BibEmailRecipientDto;
use App\Application\Race\BibEmail\ParseBibEmailCsv;
use App\Application\Race\BibEmail\SendBibEmailMessage;
use App\Domain\Notification\Repository\EmailSendLogRepositoryInterface;
use App\Domain\Race\Entity\RaceEdition;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/admin')]
class AdminBibEmailController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
        private ParseBibEmailCsv $csvParser,
        private RaceEditionRepositoryInterface $raceEditionRepository,
        private EmailSendLogRepositoryInterface $emailSendLogRepository,
    ) {
    }

    #[Route('/bib-emails', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $raceEditionId = $request->query->get('editionId');
        $envelope = $this->queryBus->dispatch(new GetEmailSendLogsQuery(
            raceEditionId: \is_string($raceEditionId) ? $raceEditionId : null,
        ));

        /** @var EmailSendLogResponseDto[] $dtos */
        $dtos = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json(['data' => array_map(fn ($dto) => $dto->toArray(), $dtos)]);
    }

    #[Route('/bib-emails/preview', methods: ['POST'])]
    public function preview(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return $this->json(['error' => 'No CSV file uploaded or invalid'], 400);
        }

        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            return $this->json(['error' => 'Cannot read CSV file'], 400);
        }

        $edition = $this->resolveEdition($request);
        if ($edition instanceof JsonResponse) {
            return $edition;
        }

        $recipients = $this->csvParser->parse($content);

        $items = array_map(function (BibEmailRecipientDto $recipient) use ($edition) {
            $existing = $this->emailSendLogRepository->findByEmailAndBibNumber(
                $recipient->email,
                $recipient->bibNumber
            );

            return [
                'name' => $recipient->name,
                'email' => $recipient->email,
                'bibNumber' => $recipient->bibNumber,
                'emailValid' => $recipient->emailValid,
                'status' => $existing?->status()->value() ?? 'not_sent',
                'errorMessage' => $existing?->errorMessage(),
                'sentAt' => $existing?->sentAt()?->format('Y-m-d H:i:s'),
            ];
        }, $recipients);

        return $this->json([
            'data' => [
                'edition' => $edition !== null ? [
                    'id' => $edition->id()->value(),
                    'name' => $edition->name(),
                    'year' => $edition->year()->value(),
                ] : null,
                'items' => $items,
            ],
        ]);
    }

    #[Route('/bib-emails/send', methods: ['POST'])]
    public function send(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $items = $data['items'] ?? [];
        $force = (bool) ($data['force'] ?? false);

        if (!\is_array($items) || $items === []) {
            return $this->json(['error' => 'No recipients provided'], 400);
        }

        $edition = $this->resolveEditionFromBody($data);
        if ($edition instanceof JsonResponse) {
            return $edition;
        }
        $raceEditionId = $edition?->id()->value();

        $user = $this->getUser();
        $sentBy = $user instanceof \App\Entity\User ? $user->getId() : null;

        $queued = 0;
        $skipped = 0;

        foreach ($items as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $email = trim((string) ($item['email'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));
            $bibNumber = trim((string) ($item['bibNumber'] ?? ''));

            if ($email === '' || $name === '' || $bibNumber === '') {
                continue;
            }

            $existing = $this->emailSendLogRepository->findByEmailAndBibNumber($email, $bibNumber);
            if ($existing !== null && $existing->status()->isSent() && !$force) {
                $skipped++;
                continue;
            }

            $isResend = $existing !== null && $existing->status()->isSent() && $force;
            $logId = Uuid::uuid4()->toString();

            if ($existing === null || $isResend) {
                $envelope = $this->commandBus->dispatch(new CreateEmailSendLogCommand(
                    id: $logId,
                    recipientEmail: $email,
                    recipientName: $name,
                    bibNumber: $bibNumber,
                    raceEditionId: $raceEditionId,
                ));
                $logId = $envelope->last(HandledStamp::class)?->getResult() ?? $logId;
            } else {
                $logId = $existing->id();
            }

            $this->commandBus->dispatch(new SendBibEmailMessage(
                logId: $logId,
                recipientEmail: $email,
                recipientName: $name,
                bibNumber: $bibNumber,
                raceEditionId: $raceEditionId,
                sentBy: $sentBy,
            ));

            $queued++;
        }

        return $this->json([
            'data' => [
                'queued' => $queued,
                'skipped' => $skipped,
            ],
        ]);
    }

    #[Route('/bib-emails/sent-counts', methods: ['GET'])]
    public function sentCounts(Request $request): JsonResponse
    {
        $raceEditionId = $request->query->get('editionId');
        $logs = \is_string($raceEditionId) && $raceEditionId !== ''
            ? $this->emailSendLogRepository->findByRaceEditionId($raceEditionId)
            : $this->emailSendLogRepository->findAll();

        $counts = [];

        foreach ($logs as $log) {
            if (!$log->status()->isSent()) {
                continue;
            }
            $email = $log->recipientEmail();
            $counts[$email] = ($counts[$email] ?? 0) + 1;
        }

        $data = [];
        foreach ($counts as $email => $count) {
            $data[] = ['email' => $email, 'count' => $count];
        }

        return $this->json(['data' => $data]);
    }

    #[Route('/bib-emails/run', methods: ['POST'])]
    public function run(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $editionId = \is_array($data) ? ($data['editionId'] ?? null) : null;

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
            'cd /var/www/backend && nohup php bin/console app:bib-emails:send --delay=3%s%s > /var/www/backend/var/log/bib-emails-runner.log 2>&1 &',
            $editionOption,
            $userOption
        );

        exec($command);

        return $this->json([
            'data' => [
                'started' => true,
                'message' => 'Envio de emails iniciado en segundo plano.',
            ],
        ]);
    }

    private function resolveEdition(Request $request): RaceEdition|JsonResponse|null
    {
        $editionId = $request->request->get('editionId') ?? $request->query->get('editionId');

        if ($editionId !== null && $editionId !== '') {
            $edition = $this->raceEditionRepository->findById(RaceEditionId::fromString((string) $editionId));
            if ($edition === null) {
                return $this->json(['error' => 'Edition not found'], 404);
            }

            return $edition;
        }

        return $this->raceEditionRepository->findActive();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveEditionFromBody(array $data): RaceEdition|JsonResponse|null
    {
        $editionId = $data['editionId'] ?? null;

        if ($editionId !== null && $editionId !== '') {
            $edition = $this->raceEditionRepository->findById(RaceEditionId::fromString((string) $editionId));
            if ($edition === null) {
                return $this->json(['error' => 'Edition not found'], 404);
            }

            return $edition;
        }

        return $this->raceEditionRepository->findActive();
    }
}
