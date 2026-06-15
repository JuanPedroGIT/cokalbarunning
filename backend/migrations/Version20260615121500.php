<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260615121500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add race_edition_id and bib_number to runners';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE runners ADD race_edition_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE runners ADD bib_number VARCHAR(20) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_runner_edition ON runners (race_edition_id)');
        $this->addSql('CREATE INDEX idx_runner_bib ON runners (bib_number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_runner_edition');
        $this->addSql('DROP INDEX idx_runner_bib');
        $this->addSql('ALTER TABLE runners DROP race_edition_id');
        $this->addSql('ALTER TABLE runners DROP bib_number');
    }
}
