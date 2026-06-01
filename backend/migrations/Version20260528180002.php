<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528180002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add edition_id to sponsors table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sponsors ADD edition_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE sponsors ADD CONSTRAINT FK_sponsors_edition_id FOREIGN KEY (edition_id) REFERENCES race_editions (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_sponsors_edition_id ON sponsors (edition_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sponsors DROP CONSTRAINT FK_sponsors_edition_id');
        $this->addSql('DROP INDEX IDX_sponsors_edition_id');
        $this->addSql('ALTER TABLE sponsors DROP edition_id');
    }
}
