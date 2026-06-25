<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260624170849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create raffle_configs table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE raffle_configs (id UUID NOT NULL, race_edition_id UUID NOT NULL, subject VARCHAR(255) DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, prize VARCHAR(255) DEFAULT NULL, draw_date VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_raffle_config_edition ON raffle_configs (race_edition_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE raffle_configs');
    }
}
