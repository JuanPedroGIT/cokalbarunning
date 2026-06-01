<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528180004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create race_documents table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE race_documents (
            id VARCHAR(36) NOT NULL,
            edition_id UUID DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(20) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('ALTER TABLE race_documents ADD CONSTRAINT FK_race_documents_edition_id FOREIGN KEY (edition_id) REFERENCES race_editions (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_race_documents_edition_id ON race_documents (edition_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE race_documents DROP CONSTRAINT FK_race_documents_edition_id');
        $this->addSql('DROP INDEX IDX_race_documents_edition_id');
        $this->addSql('DROP TABLE race_documents');
    }
}
