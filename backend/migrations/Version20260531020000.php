<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260531020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add solidarity_cause and solidarity_url to race_editions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE race_editions ADD solidarity_cause VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE race_editions ADD solidarity_url VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE race_editions DROP solidarity_cause');
        $this->addSql('ALTER TABLE race_editions DROP solidarity_url');
    }
}
