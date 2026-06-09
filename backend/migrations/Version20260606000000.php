<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260606000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create social_publish_logs table for social network publishing tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE social_publish_logs (
            id UUID NOT NULL,
            post_id UUID NOT NULL,
            network VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL,
            published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            external_url VARCHAR(500) DEFAULT NULL,
            published_by VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_post_network ON social_publish_logs (post_id, network)');
        $this->addSql('CREATE INDEX idx_status ON social_publish_logs (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE social_publish_logs');
    }
}
