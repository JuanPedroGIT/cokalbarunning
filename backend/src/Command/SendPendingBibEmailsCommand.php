<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Entity\EmailSendLog as OrmEmailSendLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[AsCommand(
    name: 'app:bib-emails:send',
    description: 'Envia los emails de dorsales pendientes',
)]
final class SendPendingBibEmailsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly RaceEditionRepositoryInterface $raceEditionRepository,
        private readonly string $senderEmail,
        private readonly int $delaySeconds,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('edition-id', null, InputOption::VALUE_REQUIRED, 'UUID de la edicion a filtrar')
            ->addOption('delay', null, InputOption::VALUE_REQUIRED, 'Segundos de espera entre envios', $this->delaySeconds)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximo de emails a enviar', 0)
            ->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'UUID del usuario admin que ejecuta el envio');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $editionId = $input->getOption('edition-id');
        $delay = (int) $input->getOption('delay');
        $limit = (int) $input->getOption('limit');
        $userId = $input->getOption('user-id');

        $criteria = ['status' => 'pending'];
        if ($editionId !== null && $editionId !== '') {
            $criteria['raceEditionId'] = $editionId;
        }

        $repository = $this->entityManager->getRepository(OrmEmailSendLog::class);
        $queryBuilder = $repository->createQueryBuilder('l')
            ->where('l.status = :status')
            ->setParameter('status', 'pending');

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
        $io->info(sprintf('Enviando %d email(s)...', $total));

        $sent = 0;
        $failed = 0;

        /** @var EmailSendLog $log */
        foreach ($logs as $index => $log) {
            try {
                $edition = $log->getRaceEditionId() !== null
                    ? $this->raceEditionRepository->findById(RaceEditionId::fromString($log->getRaceEditionId()))
                    : null;

                $editionName = $edition?->name() ?? 'IX Carrera Solidaria "Un Nuevo Impulso"';
                $editionDate = $edition?->date()?->format('l, d \d\e F \d\e Y') ?? 'Domingo, 5 de julio de 2026';
                $editionLocation = $edition?->location() ?? 'Calle Larga, Coca de Alba (Salamanca)';

                $html = $this->twig->render('emails/bib_assigned.html.twig', [
                    'nombre' => $log->getRecipientName(),
                    'dorsal' => $log->getBibNumber(),
                    'editionName' => $editionName,
                    'editionDate' => $editionDate,
                    'editionLocation' => $editionLocation,
                ]);

                $textPlain = sprintf(
                    "¡Hola %s!\n\nTu dorsal asignado es: %s\n\nEdición: %s\nFecha: %s\nLugar: %s\n\nGracias por participar.",
                    $log->getRecipientName(),
                    $log->getBibNumber(),
                    $editionName,
                    $editionDate,
                    $editionLocation,
                );

                $email = (new Email())
                    ->from($this->senderEmail)
                    ->to($log->getRecipientEmail())
                    ->subject(sprintf('Tu dorsal para la carrera - %s', $log->getRecipientName()))
                    ->text($textPlain)
                    ->html($html);

                $this->mailer->send($email);

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
}
