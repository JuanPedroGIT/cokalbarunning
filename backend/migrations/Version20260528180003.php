<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528180003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename photo columns to align with R2 storage PRD';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE photos RENAME COLUMN filename TO original_path');
        $this->addSql('ALTER TABLE photos RENAME COLUMN thumbnail_filename TO thumb_path');
        $this->addSql('ALTER TABLE photos RENAME COLUMN caption TO alt_text');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE photos RENAME COLUMN original_path TO filename');
        $this->addSql('ALTER TABLE photos RENAME COLUMN thumb_path TO thumbnail_filename');
        $this->addSql('ALTER TABLE photos RENAME COLUMN alt_text TO caption');
    }
}
