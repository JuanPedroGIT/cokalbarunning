<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Photo;
use App\Entity\RaceEdition;
use App\Entity\Sponsor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-storage-urls-to-paths',
    description: 'Migrates full URLs stored in database to relative paths',
)]
class MigrateStorageUrlsToPathsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private string $publicUrl,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be changed without modifying the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');

        if ($isDryRun) {
            $io->note('Running in dry-run mode. No changes will be made.');
        }

        $publicUrl = rtrim($this->publicUrl, '/');
        $changed = 0;
        $skipped = 0;

        // Migrate photos
        $photos = $this->em->getRepository(Photo::class)->findAll();
        foreach ($photos as $photo) {
            $originalPath = $photo->getOriginalPath();
            $newPath = $this->extractPath($originalPath, $publicUrl);

            if ($newPath !== $originalPath) {
                if (!$isDryRun) {
                    $photo->setOriginalPath($newPath);
                }
                $changed++;
                $io->text(sprintf('[Photo %s] %s -> %s', $photo->getId(), $originalPath, $newPath));
            } else {
                $skipped++;
            }
        }

        // Migrate race editions
        $editions = $this->em->getRepository(RaceEdition::class)->findAll();
        foreach ($editions as $edition) {
            $posterUrl = $edition->getPosterUrl();
            if ($posterUrl !== null) {
                $newPath = $this->extractPath($posterUrl, $publicUrl);

                if ($newPath !== $posterUrl) {
                    if (!$isDryRun) {
                        $edition->setPosterUrl($newPath);
                    }
                    $changed++;
                    $io->text(sprintf('[RaceEdition %s] %s -> %s', $edition->getId(), $posterUrl, $newPath));
                } else {
                    $skipped++;
                }
            }
        }

        // Migrate sponsors
        $sponsors = $this->em->getRepository(Sponsor::class)->findAll();
        foreach ($sponsors as $sponsor) {
            $logoUrl = $sponsor->getLogoUrl();
            if ($logoUrl !== null) {
                $newPath = $this->extractPath($logoUrl, $publicUrl);

                if ($newPath !== $logoUrl) {
                    if (!$isDryRun) {
                        $sponsor->setLogoUrl($newPath);
                    }
                    $changed++;
                    $io->text(sprintf('[Sponsor %s] %s -> %s', $sponsor->getId(), $logoUrl, $newPath));
                } else {
                    $skipped++;
                }
            }
        }

        if (!$isDryRun && $changed > 0) {
            $this->em->flush();
        }

        $io->success(sprintf('Migration complete. Changed: %d, Skipped: %d', $changed, $skipped));

        return Command::SUCCESS;
    }

    private function extractPath(string $value, string $publicUrl): string
    {
        // If it's already a relative path (does not start with http), return as-is
        if (!str_starts_with($value, 'http')) {
            return $value;
        }

        // Remove the public URL prefix
        if (str_starts_with($value, $publicUrl . '/')) {
            return substr($value, strlen($publicUrl) + 1);
        }

        // If URL does not match expected public URL, try to extract path from URL
        $parsed = parse_url($value);
        if (isset($parsed['path'])) {
            return ltrim($parsed['path'], '/');
        }

        return $value;
    }
}
