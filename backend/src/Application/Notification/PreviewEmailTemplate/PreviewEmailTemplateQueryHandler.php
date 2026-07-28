<?php

declare(strict_types=1);

namespace App\Application\Notification\PreviewEmailTemplate;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Notification\Repository\EmailConfigRepositoryInterface;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Domain\Registration\Repository\RunnerRepositoryInterface;
use App\Infrastructure\Mail\EmailTemplateResolver;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Twig\Environment;

#[AsMessageHandler]
final class PreviewEmailTemplateQueryHandler
{
    public function __construct(
        private EmailConfigRepositoryInterface $emailConfigRepository,
        private EmailTemplateResolver $templateResolver,
        private Environment $twig,
        private RaceEditionRepositoryInterface $raceEditionRepository,
        private RunnerRepositoryInterface $runnerRepository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(PreviewEmailTemplateQuery $query): array
    {
        $config = $this->emailConfigRepository->findById($query->emailConfigId);
        if ($config === null) {
            throw new \RuntimeException('Email configuration not found');
        }

        $templateConfig = $this->templateResolver->resolve($config->type());

        $edition = $this->raceEditionRepository->findById(RaceEditionId::fromString($config->raceEditionId()));
        $editionName = $edition?->name() ?? 'Carrera';
        $editionYear = $edition?->year()->value() ?? '';
        $editionLocation = $edition?->location() ?? '';

        // Get a sample runner for preview
        $runners = $this->runnerRepository->findByEditionId($config->raceEditionId());
        $sampleRunner = \count($runners) > 0 ? $runners[0] : null;

        $runnerName = $sampleRunner?->fullName() ?? 'María García';
        $runnerEmail = $sampleRunner?->email() ?? 'ejemplo@email.com';
        $runnerDorsal = $sampleRunner?->bibNumber() ?? '42';

        $prizeImageUrl = $config->prizeImageUrl();
        if ($prizeImageUrl !== null && $prizeImageUrl !== '') {
            $prizeImageUrl = $this->storage->url($prizeImageUrl);
        }

        $templateVars = [
            'nombre' => $runnerName,
            'runner' => ['name' => $runnerName],
            'email' => $runnerEmail,
            'dorsal' => $runnerDorsal,
            'reference' => $runnerDorsal,
            'editionName' => $editionName,
            'editionYear' => $editionYear,
            'editionLocation' => $editionLocation,
            'metadata' => [
                'subject' => $config->subject(),
                'title' => $config->title(),
                'description' => $config->description(),
                'prize' => $config->prize(),
                'drawDate' => $config->drawDate(),
                'prizeImageUrl' => $prizeImageUrl,
            ],
        ];

        $html = $this->twig->render($templateConfig['template'], $templateVars);

        $subjectTemplate = $config->subject() ?? $templateConfig['subject'];
        $subject = $this->resolveSubject($subjectTemplate, $templateVars);

        return [
            'subject' => $subject,
            'html' => $html,
            'template' => $templateConfig['template'],
            'configId' => $config->id(),
            'configType' => $config->type(),
        ];
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function resolveSubject(string $template, array $vars): string
    {
        $placeholders = [
            '{nombre}' => (string) ($vars['nombre'] ?? ''),
            '{runnerName}' => (string) ($vars['nombre'] ?? ''),
            '{editionName}' => (string) ($vars['editionName'] ?? ''),
            '{editionYear}' => (string) ($vars['editionYear'] ?? ''),
            '{title}' => (string) ($vars['metadata']['title'] ?? ''),
            '{drawDate}' => (string) ($vars['metadata']['drawDate'] ?? ''),
            '{prize}' => (string) ($vars['metadata']['prize'] ?? ''),
            '{description}' => (string) ($vars['metadata']['description'] ?? ''),
            '{reference}' => (string) ($vars['reference'] ?? ''),
            '{dorsal}' => (string) ($vars['dorsal'] ?? ''),
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }
}
