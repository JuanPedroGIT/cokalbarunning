<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260531030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_by and updated_by audit columns';
    }

    public function up(Schema $schema): void
    {
        $tables = ['race_editions', 'sponsors', 'photos', 'club_members', 'blog_posts'];
        foreach ($tables as $table) {
            $this->addSql("ALTER TABLE $table ADD created_by VARCHAR(255) DEFAULT NULL");
            $this->addSql("ALTER TABLE $table ADD updated_by VARCHAR(255) DEFAULT NULL");
        }
    }

    public function down(Schema $schema): void
    {
        $tables = ['race_editions', 'sponsors', 'photos', 'club_members', 'blog_posts'];
        foreach ($tables as $table) {
            $this->addSql("ALTER TABLE $table DROP created_by");
            $this->addSql("ALTER TABLE $table DROP updated_by");
        }
    }
}
