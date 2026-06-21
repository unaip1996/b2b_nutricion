<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260522202434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE EXTENSION IF NOT EXISTS vector');
        $this->addSql('CREATE TABLE document_chunks (id UUID NOT NULL, content TEXT NOT NULL, embedding vector(1536) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE patients (id UUID NOT NULL, medical_history_number VARCHAR(255) NOT NULL, gender VARCHAR(50) NOT NULL, birth_date DATE NOT NULL, active_status BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2CCC2E2CBC6F872 ON patients (medical_history_number)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE document_chunks');
        $this->addSql('DROP TABLE patients');
    }
}
