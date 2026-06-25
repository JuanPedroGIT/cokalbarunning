<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add type to email_send_logs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE email_send_logs ADD type VARCHAR(20) DEFAULT 'bib' NOT NULL");
        $this->addSql("ALTER TABLE email_send_logs ALTER type DROP DEFAULT");
        $this->addSql('CREATE INDEX idx_email_send_log_type ON email_send_logs (type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_email_send_log_type');
        $this->addSql('ALTER TABLE email_send_logs DROP type');
    }
}
