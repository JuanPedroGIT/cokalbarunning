<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Domain\Notification\ValueObject\EmailType;

final class EmailTemplateResolver
{
    public function resolve(string $type): array
    {
        return match ($type) {
            EmailType::BIB => [
                'template' => 'emails/bib_assigned.html.twig',
                'subject' => 'Tu dorsal para la carrera - {nombre}',
            ],
            EmailType::LAST_INSTRUCTIONS => [
                'template' => 'emails/last_instructions.html.twig',
                'subject' => 'Últimas indicaciones - {editionName}',
            ],
            EmailType::RAFFLE => [
                'template' => 'emails/raffle.html.twig',
                'subject' => 'Sorteo {title} - {prize}',
            ],
            EmailType::THANKS => [
                'template' => 'emails/thanks.html.twig',
                'subject' => 'Gracias por participar - {nombre}',
            ],
            default => throw new \InvalidArgumentException(sprintf('Unsupported email type: %s', $type)),
        };
    }
}
