<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604180001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add trophy_url to race_editions table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE race_editions ADD trophy_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE race_editions DROP trophy_url');
    }
}
