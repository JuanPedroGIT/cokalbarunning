<?php

declare(strict_types=1);

namespace App\Application\Race\BibEmail;

use App\Domain\Notification\Repository\EmailSendLogRepositoryInterface;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[AsMessageHandler]
final class SendBibEmailHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private EmailSendLogRepositoryInterface $logRepository,
        private RaceEditionRepositoryInterface $raceEditionRepository,
        private string $senderEmail,
        private int $delaySeconds = 3,
    ) {
    }

    public function __invoke(SendBibEmailMessage $message): void
    {
        $log = $this->logRepository->findById($message->logId);
        if ($log === null) {
            return;
        }

        $edition = $message->raceEditionId !== null
            ? $this->raceEditionRepository->findById(RaceEditionId::fromString($message->raceEditionId))
            : null;

        try {
            $editionName = $edition?->name() ?? 'IX Carrera Solidaria "Un Nuevo Impulso"';
            $editionDate = $edition?->date()?->format('l, d \d\e F \d\e Y') ?? 'Domingo, 5 de julio de 2026';
            $editionLocation = $edition?->location() ?? 'Calle Larga, Coca de Alba (Salamanca)';

            $html = $this->twig->render('emails/bib_assigned.html.twig', [
                'nombre' => $message->recipientName,
                'dorsal' => $message->bibNumber,
                'editionName' => $editionName,
                'editionDate' => $editionDate,
                'editionLocation' => $editionLocation,
            ]);

            $textPlain = sprintf(
                "¡Hola %s!\n\nTu dorsal asignado es: %s\n\nEdición: %s\nFecha: %s\nLugar: %s\n\nGracias por participar.",
                $message->recipientName,
                $message->bibNumber,
                $editionName,
                $editionDate,
                $editionLocation,
            );

            $email = (new Email())
                ->from($this->senderEmail)
                ->to($message->recipientEmail)
                ->subject(sprintf('Tu dorsal para la carrera - %s', $message->recipientName))
                ->text($textPlain)
                ->html($html);

            $this->mailer->send($email);

            if ($message->sentBy !== null) {
                $log->markAsSentBy($message->sentBy);
            } else {
                $log->markAsSent();
            }

            if ($this->delaySeconds > 0) {
                sleep($this->delaySeconds);
            }
        } catch (\Throwable $e) {
            $log->markAsError($e->getMessage());
        }

        $this->logRepository->save($log);
    }
}
