<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260708140250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bulksheet_record ADD sku VARCHAR(100) DEFAULT NULL');

        // 1. Ajouter autobid en NULLABLE d'abord
        $this->addSql('ALTER TABLE bulksheet_record ADD autobid DOUBLE PRECISION DEFAULT NULL');

        // 2. Remplir les lignes existantes
        $this->addSql('UPDATE bulksheet_record SET autobid = 0.35 WHERE autobid IS NULL');

        // 3. Rendre la colonne NOT NULL
        $this->addSql('ALTER TABLE bulksheet_record ALTER COLUMN autobid SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bulksheet_record DROP sku');
        $this->addSql('ALTER TABLE bulksheet_record DROP autobid');
    }
}
