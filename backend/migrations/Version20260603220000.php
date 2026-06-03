<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_id to club_members';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE club_members ADD user_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CLUB_MEMBER_USER ON club_members (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_CLUB_MEMBER_USER');
        $this->addSql('ALTER TABLE club_members DROP user_id');
    }
}
