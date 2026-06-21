<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260618121940 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document_chunks ALTER embedding TYPE vector(1536)');
        $this->addSql('ALTER TABLE measurements ALTER weight DROP NOT NULL');
        $this->addSql('ALTER TABLE measurements ALTER body_fat_percentage DROP NOT NULL');
        $this->addSql('ALTER TABLE measurements ALTER muscle_mass DROP NOT NULL');
        $this->addSql('ALTER TABLE measurements ALTER waist_circumference DROP NOT NULL');
        $this->addSql('ALTER TABLE measurements ALTER height DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document_chunks ALTER embedding TYPE vector');
        $this->addSql('ALTER TABLE measurements ALTER weight SET NOT NULL');
        $this->addSql('ALTER TABLE measurements ALTER height SET NOT NULL');
        $this->addSql('ALTER TABLE measurements ALTER body_fat_percentage SET NOT NULL');
        $this->addSql('ALTER TABLE measurements ALTER muscle_mass SET NOT NULL');
        $this->addSql('ALTER TABLE measurements ALTER waist_circumference SET NOT NULL');
    }
}
