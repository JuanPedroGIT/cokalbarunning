<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bio column to club_members';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE club_members ADD bio TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE club_members DROP bio');
    }
}
