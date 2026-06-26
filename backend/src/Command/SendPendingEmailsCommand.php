<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Notification\ValueObject\EmailType;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Entity\EmailSendLog as OrmEmailSendLog;
use App\Repository\EmailConfigRepository;
use App\Infrastructure\Mail\BrevoMailer;
use App\Infrastructure\Mail\EmailTemplateResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[AsCommand(
    name: 'app:emails:send',
    description: 'Envia los emails pendientes por tipo',
)]
final class SendPendingEmailsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Environment $twig,
        private readonly BrevoMailer $brevoMailer,
        private readonly RaceEditionRepositoryInterface $raceEditionRepository,
        private readonly EmailConfigRepository $emailConfigRepository,
        private readonly EmailTemplateResolver $templateResolver,
        private readonly string $senderEmail,
        private readonly int $delaySeconds,
        private readonly ?string $bccEmail = null,
        private readonly string $publicUrl = '',
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Tipo de email: bib, raffle, last_instructions')
            ->addOption('edition-id', null, InputOption::VALUE_REQUIRED, 'UUID de la edicion a filtrar')
            ->addOption('delay', null, InputOption::VALUE_REQUIRED, 'Segundos de espera entre envios', $this->delaySeconds)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximo de emails a enviar', 0)
            ->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'UUID del usuario admin que ejecuta el envio');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $type = $input->getOption('type');
        if (!\is_string($type) || $type === '') {
            $io->error('Debes indicar el tipo de email con --type.');

            return Command::FAILURE;
        }

        try {
            new EmailType($type);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $editionId = $input->getOption('edition-id');
        $delay = (int) $input->getOption('delay');
        $limit = (int) $input->getOption('limit');
        $userId = $input->getOption('user-id');

        $queryBuilder = $this->entityManager->getRepository(OrmEmailSendLog::class)
            ->createQueryBuilder('l')
            ->where('l.status = :status')
            ->andWhere('l.type = :type')
            ->setParameter('status', 'pending')
            ->setParameter('type', $type);

        if ($editionId !== null && $editionId !== '') {
            $queryBuilder
                ->andWhere('l.raceEditionId = :editionId')
                ->setParameter('editionId', $editionId);
        }

        if ($limit > 0) {
            $queryBuilder->setMaxResults($limit);
        }

        $logs = $queryBuilder->getQuery()->getResult();

        if ($logs === []) {
            $io->info('No hay emails pendientes.');

            return Command::SUCCESS;
        }

        $total = \count($logs);
        $io->info(sprintf('Enviando %d email(s) de tipo "%s"...', $total, $type));

        $sent = 0;
        $failed = 0;

        $templateConfig = $this->templateResolver->resolve($type);

        /** @var OrmEmailSendLog $log */
        foreach ($logs as $index => $log) {
            try {
                $edition = $log->getRaceEditionId() !== null
                    ? $this->raceEditionRepository->findById(RaceEditionId::fromString($log->getRaceEditionId()))
                    : null;

                $editionName = $edition?->name() ?? 'IX Carrera Solidaria "Un Nuevo Impulso"';
                $editionDate = $edition !== null ? $this->formatEditionDate($edition->date()) : null;
                $editionLocation = $edition?->location() ?? 'Calle Larga, Coca de Alba (Salamanca)';

                $metadata = $this->resolveMetadata($log, $type);

                $templateVars = [
                    'nombre' => $log->getRecipientName(),
                    'firstName' => $this->extractFirstName($log->getRecipientName()),
                    'lastName' => $this->extractLastName($log->getRecipientName()),
                    'editionName' => $editionName,
                    'editionDate' => $editionDate,
                    'editionLocation' => $editionLocation,
                    'dorsal' => $log->getReference(),
                    'reference' => $log->getReference(),
                    'metadata' => $metadata,
                ];

                if ($type === EmailType::BIB) {
                    $templateVars['dorsal'] = $log->getReference();
                }

                $html = $this->twig->render($templateConfig['template'], $templateVars);

                $subjectTemplate = !empty($metadata['subject']) ? $metadata['subject'] : $templateConfig['subject'];
                $subject = $this->renderSubject($subjectTemplate, $templateVars, $metadata);

                $email = (new Email())
                    ->from($this->senderEmail)
                    ->to(new Address($log->getRecipientEmail(), $log->getRecipientName()))
                    ->subject($subject)
                    ->html($html);

                $bccEmail = $metadata['bccEmail'] ?? $this->bccEmail;
                if ($bccEmail !== null && $bccEmail !== '') {
                    $email->addBcc(new Address($bccEmail, 'Cokalba Running'));
                }

                $this->brevoMailer->send($email);

                $log->markAsSent();
                if ($userId !== null && $userId !== '') {
                    $log->setSentBy($userId);
                }
                $sent++;
            } catch (\Throwable $e) {
                $log->markAsError($e->getMessage());
                $failed++;
            }

            $this->entityManager->flush();

            $io->writeln(sprintf(
                '[%d/%d] %s -> %s',
                $index + 1,
                $total,
                $log->getRecipientEmail(),
                $log->getStatus()
            ));

            if ($delay > 0 && $index < $total - 1) {
                sleep($delay);
            }
        }

        $io->success(sprintf('Proceso finalizado. Enviados: %d. Fallidos: %d.', $sent, $failed));

        return Command::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveMetadata(OrmEmailSendLog $log, string $type): array
    {
        $logMetadata = $log->getMetadata() ?? [];

        $configData = [];
        $raceEditionId = $log->getRaceEditionId();
        if ($raceEditionId !== null && $raceEditionId !== '') {
            $config = $this->emailConfigRepository->findByRaceEditionIdAndType($raceEditionId, $type);
            if ($config !== null) {
                $configData = [
                    'subject' => $config->getSubject(),
                    'title' => $config->getTitle(),
                    'description' => $config->getDescription(),
                    'prizeImageUrl' => $config->getPrizeImageUrl(),
                ];
                if ($type === EmailType::RAFFLE) {
                    $configData['prize'] = $config->getPrize();
                    $configData['drawDate'] = $config->getDrawDate();
                }
            }
        }

        $result = array_merge($configData, $logMetadata);

        // Ensure prizeImageUrl is an absolute URL
        if (!empty($result['prizeImageUrl']) && !str_starts_with($result['prizeImageUrl'], 'http')) {
            $result['prizeImageUrl'] = $this->publicUrl . '/' . ltrim($result['prizeImageUrl'], '/');
        }

        return $result;
    }

    private function renderSubject(string $template, array $vars, array $metadata = []): string
    {
        $subject = $template;

        foreach (['title', 'drawDate', 'prize', 'description'] as $key) {
            $value = $metadata[$key] ?? null;
            if (\is_string($value) || \is_numeric($value)) {
                $subject = str_replace('{' . $key . '}', (string) $value, $subject);
            }
        }

        $flatVars = $this->flattenVars($vars);
        foreach ($flatVars as $key => $value) {
            if (\is_string($value) || \is_numeric($value)) {
                $subject = str_replace('{' . $key . '}', (string) $value, $subject);
            }
        }

        return $subject;
    }

    /**
     * @param array<string, mixed> $vars
     *
     * @return array<string, mixed>
     */
    private function flattenVars(array $vars, string $prefix = ''): array
    {
        $result = [];
        foreach ($vars as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (\is_array($value)) {
                $result = array_merge($result, $this->flattenVars($value, $fullKey));
            } else {
                $result[$fullKey] = $value;
            }
        }

        return $result;
    }

    private function extractFirstName(string $fullName): string
    {
        $trimmed = trim($fullName);
        $spacePos = strpos($trimmed, ' ');

        if ($spacePos === false) {
            return $trimmed;
        }

        return substr($trimmed, 0, $spacePos);
    }

    private function extractLastName(string $fullName): string
    {
        $trimmed = trim($fullName);
        $spacePos = strpos($trimmed, ' ');

        if ($spacePos === false) {
            return '';
        }

        return trim(substr($trimmed, $spacePos + 1));
    }

    private function formatEditionDate(\DateTimeImmutable $date): string
    {
        $days = [
            'Monday' => 'lunes',
            'Tuesday' => 'martes',
            'Wednesday' => 'miércoles',
            'Thursday' => 'jueves',
            'Friday' => 'viernes',
            'Saturday' => 'sábado',
            'Sunday' => 'domingo',
        ];

        $months = [
            'January' => 'enero',
            'February' => 'febrero',
            'March' => 'marzo',
            'April' => 'abril',
            'May' => 'mayo',
            'June' => 'junio',
            'July' => 'julio',
            'August' => 'agosto',
            'September' => 'septiembre',
            'October' => 'octubre',
            'November' => 'noviembre',
            'December' => 'diciembre',
        ];

        $dayName = $days[$date->format('l')];
        $monthName = $months[$date->format('F')];

        return sprintf('%s, %d de %s de %d', $dayName, (int) $date->format('j'), $monthName, (int) $date->format('Y'));
    }
}
