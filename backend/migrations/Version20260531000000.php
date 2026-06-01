<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260531000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create club_members table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE club_members (
            id VARCHAR(36) NOT NULL,
            name VARCHAR(255) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            photo_path VARCHAR(500) DEFAULT NULL,
            is_active BOOLEAN NOT NULL DEFAULT true,
            sort_order INT NOT NULL DEFAULT 0,
            PRIMARY KEY(id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE club_members');
    }
}
