<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260617213452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document_chunks ALTER embedding TYPE vector(1536)');
        $this->addSql('ALTER TABLE patients ADD name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE patients ADD email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE patients ADD phone VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE patients ADD pathologies TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE patients ADD nutritional_goal VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE patients ADD clinical_notes TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document_chunks ALTER embedding TYPE vector');
        $this->addSql('ALTER TABLE patients DROP name');
        $this->addSql('ALTER TABLE patients DROP email');
        $this->addSql('ALTER TABLE patients DROP phone');
        $this->addSql('ALTER TABLE patients DROP pathologies');
        $this->addSql('ALTER TABLE patients DROP nutritional_goal');
        $this->addSql('ALTER TABLE patients DROP clinical_notes');
    }
}
