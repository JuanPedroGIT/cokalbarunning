<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260615143450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add show_bib_search to race_editions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE race_editions ADD show_bib_search BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE race_editions ALTER show_bib_search DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE race_editions DROP show_bib_search');
    }
}
