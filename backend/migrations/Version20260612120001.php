<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260612120001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add type column to blog_posts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog_posts ADD type INT NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog_posts DROP type');
    }
}
