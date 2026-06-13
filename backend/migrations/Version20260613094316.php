<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260613094316 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email_send_logs (id VARCHAR(36) NOT NULL, race_edition_id VARCHAR(36) DEFAULT NULL, recipient_email VARCHAR(255) NOT NULL, recipient_name VARCHAR(255) NOT NULL, bib_number VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, error_message TEXT DEFAULT NULL, sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_email_send_log_edition ON email_send_logs (race_edition_id)');
        $this->addSql('CREATE INDEX idx_email_send_log_email ON email_send_logs (recipient_email)');
        $this->addSql('CREATE INDEX idx_email_send_log_status ON email_send_logs (status)');
        $this->addSql('ALTER TABLE club_members ALTER is_active DROP DEFAULT');
        $this->addSql('ALTER TABLE club_members ALTER sort_order DROP DEFAULT');
        $this->addSql('ALTER INDEX uniq_club_member_user RENAME TO UNIQ_48E8777DA76ED395');
        $this->addSql('ALTER TABLE photos DROP created_by');
        $this->addSql('ALTER TABLE photos DROP updated_by');
        $this->addSql('ALTER TABLE race_documents DROP CONSTRAINT fk_race_documents_edition_id');
        $this->addSql('ALTER TABLE race_documents ADD CONSTRAINT FK_7B36329574281A5E FOREIGN KEY (edition_id) REFERENCES race_editions (id) NOT DEFERRABLE');
        $this->addSql('ALTER INDEX idx_race_documents_edition_id RENAME TO IDX_7B36329574281A5E');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE email_send_logs');
        $this->addSql('ALTER TABLE club_members ALTER is_active SET DEFAULT true');
        $this->addSql('ALTER TABLE club_members ALTER sort_order SET DEFAULT 0');
        $this->addSql('ALTER INDEX uniq_48e8777da76ed395 RENAME TO uniq_club_member_user');
        $this->addSql('ALTER TABLE photos ADD created_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE photos ADD updated_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE race_documents DROP CONSTRAINT FK_7B36329574281A5E');
        $this->addSql('ALTER TABLE race_documents ADD CONSTRAINT fk_race_documents_edition_id FOREIGN KEY (edition_id) REFERENCES race_editions (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_7b36329574281a5e RENAME TO idx_race_documents_edition_id');
    }
}
