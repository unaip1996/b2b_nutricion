<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260707124534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE clinical_documents (id UUID NOT NULL, file_name VARCHAR(255) NOT NULL, ingested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE document_chunks ADD clinical_document_id UUID NOT NULL');
        $this->addSql('ALTER TABLE document_chunks ADD CONSTRAINT FK_792F75D3FD817EBB FOREIGN KEY (clinical_document_id) REFERENCES clinical_documents (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_792F75D3FD817EBB ON document_chunks (clinical_document_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE clinical_documents');
        $this->addSql('ALTER TABLE document_chunks DROP CONSTRAINT FK_792F75D3FD817EBB');
        $this->addSql('DROP INDEX IDX_792F75D3FD817EBB');
        $this->addSql('ALTER TABLE document_chunks DROP clinical_document_id');
    }
}
