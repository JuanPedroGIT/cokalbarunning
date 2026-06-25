<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename bib_number to reference in email_send_logs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE email_send_logs RENAME COLUMN bib_number TO reference');
        $this->addSql('ALTER TABLE email_send_logs ALTER reference TYPE VARCHAR(50)');
        $this->addSql('ALTER TABLE email_send_logs ALTER reference DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE email_send_logs ALTER reference SET NOT NULL');
        $this->addSql('ALTER TABLE email_send_logs ALTER reference TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE email_send_logs RENAME COLUMN reference TO bib_number');
    }
}
