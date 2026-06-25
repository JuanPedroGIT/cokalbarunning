<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260625100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename raffle_configs to emails_config and add type column';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $this->addSql('ALTER TABLE raffle_configs RENAME TO emails_config');
        $this->addSql("ALTER TABLE emails_config ADD type VARCHAR(20) NOT NULL DEFAULT 'raffle'");
        $this->addSql('CREATE UNIQUE INDEX idx_emails_config_edition_type ON emails_config (race_edition_id, type)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Migration can only be executed safely on PostgreSQL.'
        );

        $this->addSql('DROP INDEX idx_emails_config_edition_type');
        $this->addSql('ALTER TABLE emails_config DROP type');
        $this->addSql('ALTER TABLE emails_config RENAME TO raffle_configs');
    }
}
