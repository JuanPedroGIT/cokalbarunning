<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260531010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add inscription_info column to race_editions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE race_editions ADD inscription_info VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE race_editions DROP inscription_info');
    }
}
