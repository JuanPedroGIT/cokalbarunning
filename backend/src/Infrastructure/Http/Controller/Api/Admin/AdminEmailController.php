<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api\Admin;

use App\Application\Notification\Command\CreateEmailSendLogCommand;
use App\Application\Notification\Query\GetEmailSendLogsQuery;
use App\Application\Notification\Response\EmailSendLogResponseDto;
use App\Application\Race\BibEmail\EmailRecipientDto;
use App\Application\Race\BibEmail\ParseEmailCsv;
use App\Domain\Notification\Repository\EmailSendLogRepositoryInterface;
use App\Domain\Notification\ValueObject\EmailType;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Service\PathGenerator;
use App\Domain\Race\Entity\RaceEdition;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Entity\EmailConfig;
use App\Entity\Runner;
use App\Repository\EmailConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/admin')]
class AdminEmailController extends AbstractController
{
    private const VALID_TYPES = [EmailType::BIB, EmailType::RAFFLE, EmailType::LAST_INSTRUCTIONS, EmailType::THANKS];

    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
        private ParseEmailCsv $csvParser,
        private RaceEditionRepositoryInterface $raceEditionRepository,
        private EmailSendLogRepositoryInterface $emailSendLogRepository,
        private EntityManagerInterface $entityManager,
        private EmailConfigRepository $emailConfigRepository,
        private StoragePort $storage,
        private PathGenerator $pathGenerator,
    ) {
    }

    #[Route('/emails/{type}', methods: ['GET'], requirements: ['type' => 'bib|raffle|last_instructions|thanks'])]
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

    #[Route('/emails/{type}/preview', methods: ['POST'], requirements: ['type' => 'bib|raffle|last_instructions|thanks'])]
    public function preview(Request $request, string $type): JsonResponse
    {
        if (!$this->isValidType($type)) {
            return $this->json(['error' => 'Invalid email type'], 400);
        }

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

        $raceEditionId = $edition?->id()->value();
        $runnersCreated = 0;
        if ($raceEditionId !== null) {
            foreach ($recipients as $recipient) {
                if ($recipient->email === '' || $recipient->emailValid === false) {
                    continue;
                }
                $this->upsertRunner($recipient->toArray(), $raceEditionId);
                $runnersCreated++;
            }
            $this->entityManager->flush();
        }

        $items = array_map(function (EmailRecipientDto $recipient) use ($type) {
            $existing = $this->emailSendLogRepository->findByEmailTypeAndReference(
                $recipient->email,
                $type,
                $recipient->reference
            );

            return [
                'firstName' => $recipient->firstName,
                'lastName' => $recipient->lastName,
                'fullName' => $recipient->fullName(),
                'email' => $recipient->email,
                'reference' => $recipient->reference,
                'club' => $recipient->club,
                'category' => $recipient->category,
                'shirtSize' => $recipient->shirtSize,
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
                'runnersCreated' => $runnersCreated,
            ],
        ]);
    }

    #[Route('/emails/{type}/send', methods: ['POST'], requirements: ['type' => 'bib|raffle|last_instructions|thanks'])]
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
        $queuedInstructions = 0;

        foreach ($items as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $email = trim((string) ($item['email'] ?? ''));
            $firstName = trim((string) ($item['firstName'] ?? ''));
            $lastName = trim((string) ($item['lastName'] ?? ''));
            $fullName = trim((string) ($item['fullName'] ?? ''));
            $reference = isset($item['reference']) ? trim((string) $item['reference']) : null;

            if ($email === '') {
                continue;
            }

            $name = $fullName !== '' ? $fullName : trim($firstName . ' ' . $lastName);
            if ($name === '') {
                continue;
            }

            if ($raceEditionId !== null) {
                $this->upsertRunner($item, $raceEditionId);
            }

            $created = $this->createOrUpdateLog(
                email: $email,
                name: $name,
                reference: $reference,
                type: $type,
                raceEditionId: $raceEditionId,
                metadata: $metadata,
                sentBy: $sentBy,
                force: $force,
            );

            if ($created === null) {
                $skipped++;
                continue;
            }

            $queued++;

            // Al enviar el sorteo se encolan automaticamente las ultimas indicaciones.
            if ($type === EmailType::RAFFLE) {
                $instructionsCreated = $this->createOrUpdateLog(
                    email: $email,
                    name: $name,
                    reference: $reference,
                    type: EmailType::LAST_INSTRUCTIONS,
                    raceEditionId: $raceEditionId,
                    metadata: [],
                    sentBy: $sentBy,
                    force: $force,
                );
                if ($instructionsCreated !== null) {
                    $queuedInstructions++;
                }
            }
        }

        if ($type === EmailType::BIB && $raceEditionId !== null && $queued > 0) {
            $ormEdition = $this->entityManager->getRepository(\App\Entity\RaceEdition::class)->find($raceEditionId);
            if ($ormEdition !== null && !$ormEdition->isShowBibSearch()) {
                $ormEdition->setShowBibSearch(true);
            }
        }

        $this->entityManager->flush();

        return $this->json([
            'data' => [
                'queued' => $queued,
                'skipped' => $skipped,
                'queuedInstructions' => $queuedInstructions,
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function createOrUpdateLog(
        string $email,
        string $name,
        ?string $reference,
        string $type,
        ?string $raceEditionId,
        array $metadata,
        ?string $sentBy,
        bool $force,
    ): ?string {
        $existing = $this->emailSendLogRepository->findByEmailTypeAndReference($email, $type, $reference);
        if ($existing !== null && $existing->status()->isSent() && !$force) {
            return null;
        }

        $isResend = $existing !== null && $existing->status()->isSent() && $force;

        if ($existing === null || $isResend) {
            $logId = Uuid::uuid4()->toString();
            $envelope = $this->commandBus->dispatch(new CreateEmailSendLogCommand(
                id: $logId,
                type: $type,
                recipientEmail: $email,
                recipientName: $name,
                reference: $reference,
                raceEditionId: $raceEditionId,
                sentBy: $sentBy,
                metadata: $metadata,
            ));

            return $envelope->last(HandledStamp::class)?->getResult() ?? $logId;
        }

        $existing->markAsPending();
        if ($sentBy !== null && $sentBy !== '') {
            $existing->assignSentBy($sentBy);
        }
        $this->emailSendLogRepository->save($existing);

        return $existing->id();
    }

    #[Route('/emails/{type}/sent-counts', methods: ['GET'], requirements: ['type' => 'bib|raffle|last_instructions|thanks'])]
    public function sentCounts(Request $request, string $type): JsonResponse
    {
        if (!$this->isValidType($type)) {
            return $this->json(['error' => 'Invalid email type'], 400);
        }

        $raceEditionId = $request->query->get('editionId');
        $logs = \is_string($raceEditionId) && $raceEditionId !== ''
            ? $this->emailSendLogRepository->findByTypeAndRaceEditionId($type, $raceEditionId)
            : $this->emailSendLogRepository->findAll();

        $counts = [];

        foreach ($logs as $log) {
            if (!$log->status()->isSent()) {
                continue;
            }
            if ($log->type()->value() !== $type) {
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

    #[Route('/emails/{type}/run', methods: ['POST'], requirements: ['type' => 'bib|raffle|last_instructions|thanks'])]
    public function run(Request $request, string $type): JsonResponse
    {
        if (!$this->isValidType($type)) {
            return $this->json(['error' => 'Invalid email type'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $editionId = \is_array($data) ? ($data['editionId'] ?? null) : null;
        $bccEmail = \is_array($data) ? ($data['bccEmail'] ?? null) : null;

        // Store BCC in pending logs metadata before running the command
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

    #[Route('/emails/{type}/config', methods: ['GET'], requirements: ['type' => 'raffle|last_instructions|thanks'])]
    public function getConfig(Request $request, string $type): JsonResponse
    {
        $editionId = $request->query->get('editionId');
        if (!\is_string($editionId) || $editionId === '') {
            return $this->json(['error' => 'Edition ID is required'], 400);
        }

        $config = $this->emailConfigRepository->findByRaceEditionIdAndType($editionId, $type);
        if ($config === null) {
            return $this->json(['data' => null]);
        }

        $data = $config->toArray();
        if (!empty($data['prizeImageUrl'])) {
            $data['prizeImageUrl'] = $this->storage->url($data['prizeImageUrl']);
        }

        return $this->json(['data' => $data]);
    }

    #[Route('/emails/{type}/config', methods: ['POST'], requirements: ['type' => 'raffle|last_instructions|thanks'])]
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

        $existing = $this->emailConfigRepository->findByRaceEditionIdAndType($editionId, $type);
        if ($existing !== null) {
            return $this->json(['error' => 'Configuration already exists for this edition. Use PUT to update.'], 409);
        }

        $config = new EmailConfig();
        $config->setId(Uuid::uuid4()->toString());
        $config->setRaceEditionId($editionId);
        $config->setType($type);
        $this->applyEmailConfigData($config, $data);

        $this->emailConfigRepository->save($config);

        $responseData = $config->toArray();
        if (!empty($responseData['prizeImageUrl'])) {
            $responseData['prizeImageUrl'] = $this->storage->url($responseData['prizeImageUrl']);
        }

        return $this->json(['data' => $responseData], 201);
    }

    #[Route('/emails/{type}/prize-image', methods: ['POST'], requirements: ['type' => 'raffle|last_instructions|thanks'])]
    public function uploadPrizeImage(Request $request, string $type): JsonResponse
    {
        $editionId = $request->request->get('editionId');
        if (!\is_string($editionId) || $editionId === '') {
            return $this->json(['error' => 'Edition ID is required'], 400);
        }

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return $this->json(['error' => 'No image file uploaded or invalid'], 400);
        }

        $config = $this->emailConfigRepository->findByRaceEditionIdAndType($editionId, $type);
        if ($config === null) {
            $config = new EmailConfig();
            $config->setId(Uuid::uuid4()->toString());
            $config->setRaceEditionId($editionId);
            $config->setType($type);
        }

        $ext = $file->guessExtension() ?: 'png';
        $path = $this->pathGenerator->emailImagePath($type, $ext);

        $previousImageUrl = $config->getPrizeImageUrl();
        if ($previousImageUrl !== null && $previousImageUrl !== '') {
            $this->storage->delete($previousImageUrl);
        }

        $this->storage->store($file, $path);
        $config->setPrizeImageUrl($path);
        $config->touch();
        $this->emailConfigRepository->save($config);

        return $this->json([
            'data' => [
                'id' => $config->getId(),
                'prizeImageUrl' => $this->storage->url($path),
                'config' => $config->toArray(),
            ],
        ]);
    }

    #[Route('/emails/{type}/config/{id}', methods: ['PUT'], requirements: ['type' => 'raffle|last_instructions|thanks'])]
    public function updateConfig(string $type, string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $config = $this->emailConfigRepository->find($id);
        if ($config === null) {
            return $this->json(['error' => 'Configuration not found'], 404);
        }

        $this->applyEmailConfigData($config, $data);
        $config->touch();
        $this->emailConfigRepository->save($config);

        $responseData = $config->toArray();
        if (!empty($responseData['prizeImageUrl'])) {
            $responseData['prizeImageUrl'] = $this->storage->url($responseData['prizeImageUrl']);
        }

        return $this->json(['data' => $responseData]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyEmailConfigData(EmailConfig $config, array $data): void
    {
        if (array_key_exists('subject', $data)) {
            $config->setSubject($data['subject'] !== null && $data['subject'] !== '' ? (string) $data['subject'] : null);
        }
        if (array_key_exists('title', $data)) {
            $config->setTitle($data['title'] !== null && $data['title'] !== '' ? (string) $data['title'] : null);
        }
        if (array_key_exists('description', $data)) {
            $config->setDescription($data['description'] !== null && $data['description'] !== '' ? (string) $data['description'] : null);
        }
        if (array_key_exists('prize', $data)) {
            $config->setPrize($data['prize'] !== null && $data['prize'] !== '' ? (string) $data['prize'] : null);
        }
        if (array_key_exists('drawDate', $data)) {
            $config->setDrawDate($data['drawDate'] !== null && $data['drawDate'] !== '' ? (string) $data['drawDate'] : null);
        }
        if (array_key_exists('prizeImageUrl', $data)) {
            $newValue = $this->normalizePrizeImageUrl($data['prizeImageUrl']);
            $oldValue = $config->getPrizeImageUrl();
            if ($newValue === null && $oldValue !== null && $oldValue !== '') {
                $this->storage->delete($oldValue);
            }
            $config->setPrizeImageUrl($newValue);
        }
    }

    private function normalizePrizeImageUrl(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $url = (string) $value;
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            return ltrim($url, '/');
        }

        $path = preg_replace('#^https?://[^/]+/#', '', $url);

        return $path !== null && $path !== '' ? ltrim($path, '/') : null;
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

    /**
     * @param array<string, mixed> $item
     */
    private function upsertRunner(array $item, string $raceEditionId): void
    {
        $email = trim((string) ($item['email'] ?? ''));
        if ($email === '') {
            return;
        }

        $firstName = trim((string) ($item['firstName'] ?? ''));
        $lastName = trim((string) ($item['lastName'] ?? ''));
        $fullName = trim((string) ($item['fullName'] ?? ''));

        if ($firstName === '' && $fullName !== '') {
            [$firstName, $lastName] = $this->splitName($fullName);
        }

        if ($firstName === '') {
            return;
        }

        $reference = isset($item['reference']) ? trim((string) $item['reference']) : null;
        $club = isset($item['club']) ? trim((string) $item['club']) : null;
        $gender = isset($item['gender']) ? trim((string) $item['gender']) : null;
        $category = isset($item['category']) ? trim((string) $item['category']) : null;

        $birthDate = null;
        $rawBirthDate = $item['birthDate'] ?? null;
        if (\is_string($rawBirthDate) && $rawBirthDate !== '') {
            $birthDate = $this->parseRunnerBirthDate($rawBirthDate);
        }

        $repository = $this->entityManager->getRepository(Runner::class);

        if ($reference !== null && $reference !== '' && !$this->isZeroBib($reference)) {
            $existingByBib = $repository->findOneBy([
                'raceEditionId' => $raceEditionId,
                'bibNumber' => $reference,
            ]);

            if ($existingByBib !== null) {
                return;
            }
        }

        $runner = $repository->findOneBy([
            'email' => $email,
            'raceEditionId' => $raceEditionId,
        ]);

        if ($runner === null) {
            $runner = new Runner();
            $runner->setId(Uuid::uuid4()->toString());
        }

        $runner->setFirstName($firstName);
        $runner->setLastName($lastName);
        $runner->setEmail($email);
        $runner->setRaceEditionId($raceEditionId);
        $runner->setBibNumber($reference);
        $runner->setClub($club);
        $runner->setGender($gender);
        $runner->setCategory($category);
        $runner->setBirthDate($birthDate);

        $this->entityManager->persist($runner);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $fullName): array
    {
        $trimmed = trim($fullName);
        $spacePos = strpos($trimmed, ' ');

        if ($spacePos === false) {
            return [$trimmed, ''];
        }

        return [
            substr($trimmed, 0, $spacePos),
            trim(substr($trimmed, $spacePos + 1)),
        ];
    }

    private function isZeroBib(string $reference): bool
    {
        return trim($reference, '0') === '';
    }

    private function parseRunnerBirthDate(string $value): ?\DateTimeImmutable
    {
        $formats = ['Y-m-d', 'd_m_Y', 'd-m-Y', 'd/m/Y'];
        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            if ($date !== false) {
                return $date->setTime(0, 0, 0);
            }
        }

        return null;
    }
}
