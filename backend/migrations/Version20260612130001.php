<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260612130001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add banner_end_at column to blog_posts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog_posts ADD banner_end_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog_posts DROP banner_end_at');
    }
}
