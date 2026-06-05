<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260605203339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add priority column to blog_posts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog_posts ADD priority INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_78B2F93262A6DC27 ON blog_posts (priority)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_78B2F93262A6DC27');
        $this->addSql('ALTER TABLE blog_posts DROP priority');
    }
}
