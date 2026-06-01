<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528180001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add shirt_url to race_editions table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE race_editions ADD shirt_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE race_editions DROP shirt_url');
    }
}
