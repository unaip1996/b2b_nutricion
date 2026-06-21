<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260528175702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE allergies (id UUID NOT NULL, name VARCHAR(255) NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D19BF1B5E237E06 ON allergies (name)');
        $this->addSql('CREATE TABLE diet_days (id UUID NOT NULL, day_number INT NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, dietary_plan_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_11FC2863CF9FD594 ON diet_days (dietary_plan_id)');
        $this->addSql('CREATE TABLE dietary_plans (id UUID NOT NULL, name VARCHAR(255) NOT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, patient_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_78E4FC576B899279 ON dietary_plans (patient_id)');
        $this->addSql('CREATE TABLE dietary_plan_document_chunks (dietary_plan_id UUID NOT NULL, document_chunk_id UUID NOT NULL, PRIMARY KEY (dietary_plan_id, document_chunk_id))');
        $this->addSql('CREATE INDEX IDX_D3259911CF9FD594 ON dietary_plan_document_chunks (dietary_plan_id)');
        $this->addSql('CREATE INDEX IDX_D3259911414B294 ON dietary_plan_document_chunks (document_chunk_id)');
        $this->addSql('CREATE TABLE food_items (id UUID NOT NULL, name VARCHAR(255) NOT NULL, kcal_per_100g DOUBLE PRECISION NOT NULL, macros JSON NOT NULL, category VARCHAR(255) NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE meal_items (id UUID NOT NULL, quantity DOUBLE PRECISION NOT NULL, unit VARCHAR(50) NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, meal_id UUID NOT NULL, food_item_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_5BD4019B639666D6 ON meal_items (meal_id)');
        $this->addSql('CREATE INDEX IDX_5BD4019B5DF08E66 ON meal_items (food_item_id)');
        $this->addSql('CREATE TABLE meals (id UUID NOT NULL, name VARCHAR(255) NOT NULL, meal_time TIME(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, diet_day_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_E229E6EAF50B59B6 ON meals (diet_day_id)');
        $this->addSql('CREATE TABLE measurements (id UUID NOT NULL, weight DOUBLE PRECISION NOT NULL, body_fat_percentage DOUBLE PRECISION NOT NULL, muscle_mass DOUBLE PRECISION NOT NULL, waist_circumference DOUBLE PRECISION NOT NULL, taken_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, patient_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_71920F216B899279 ON measurements (patient_id)');
        $this->addSql('CREATE TABLE nutritionist_profiles (id UUID NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, account_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_25C479AD9B6B5FBA ON nutritionist_profiles (account_id)');
        $this->addSql('CREATE TABLE patient_allergies (patient_id UUID NOT NULL, allergy_id UUID NOT NULL, PRIMARY KEY (patient_id, allergy_id))');
        $this->addSql('CREATE INDEX IDX_2B926D246B899279 ON patient_allergies (patient_id)');
        $this->addSql('CREATE INDEX IDX_2B926D24DBFD579D ON patient_allergies (allergy_id)');
        $this->addSql('CREATE TABLE users (id UUID NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, last_login TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('ALTER TABLE diet_days ADD CONSTRAINT FK_11FC2863CF9FD594 FOREIGN KEY (dietary_plan_id) REFERENCES dietary_plans (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE dietary_plans ADD CONSTRAINT FK_78E4FC576B899279 FOREIGN KEY (patient_id) REFERENCES patients (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE dietary_plan_document_chunks ADD CONSTRAINT FK_D3259911CF9FD594 FOREIGN KEY (dietary_plan_id) REFERENCES dietary_plans (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dietary_plan_document_chunks ADD CONSTRAINT FK_D3259911414B294 FOREIGN KEY (document_chunk_id) REFERENCES document_chunks (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE meal_items ADD CONSTRAINT FK_5BD4019B639666D6 FOREIGN KEY (meal_id) REFERENCES meals (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE meal_items ADD CONSTRAINT FK_5BD4019B5DF08E66 FOREIGN KEY (food_item_id) REFERENCES food_items (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE meals ADD CONSTRAINT FK_E229E6EAF50B59B6 FOREIGN KEY (diet_day_id) REFERENCES diet_days (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE measurements ADD CONSTRAINT FK_71920F216B899279 FOREIGN KEY (patient_id) REFERENCES patients (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE nutritionist_profiles ADD CONSTRAINT FK_25C479AD9B6B5FBA FOREIGN KEY (account_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE patient_allergies ADD CONSTRAINT FK_2B926D246B899279 FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE patient_allergies ADD CONSTRAINT FK_2B926D24DBFD579D FOREIGN KEY (allergy_id) REFERENCES allergies (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE document_chunks ADD metadata JSON NOT NULL');
        $this->addSql('ALTER TABLE document_chunks ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE document_chunks ALTER embedding TYPE vector(1536)');
        $this->addSql('ALTER TABLE patients ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE patients ADD nutritionist_profile_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE patients ADD CONSTRAINT FK_2CCC2E2CBC1EF62C FOREIGN KEY (nutritionist_profile_id) REFERENCES nutritionist_profiles (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_2CCC2E2CBC1EF62C ON patients (nutritionist_profile_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE diet_days DROP CONSTRAINT FK_11FC2863CF9FD594');
        $this->addSql('ALTER TABLE dietary_plans DROP CONSTRAINT FK_78E4FC576B899279');
        $this->addSql('ALTER TABLE dietary_plan_document_chunks DROP CONSTRAINT FK_D3259911CF9FD594');
        $this->addSql('ALTER TABLE dietary_plan_document_chunks DROP CONSTRAINT FK_D3259911414B294');
        $this->addSql('ALTER TABLE meal_items DROP CONSTRAINT FK_5BD4019B639666D6');
        $this->addSql('ALTER TABLE meal_items DROP CONSTRAINT FK_5BD4019B5DF08E66');
        $this->addSql('ALTER TABLE meals DROP CONSTRAINT FK_E229E6EAF50B59B6');
        $this->addSql('ALTER TABLE measurements DROP CONSTRAINT FK_71920F216B899279');
        $this->addSql('ALTER TABLE nutritionist_profiles DROP CONSTRAINT FK_25C479AD9B6B5FBA');
        $this->addSql('ALTER TABLE patient_allergies DROP CONSTRAINT FK_2B926D246B899279');
        $this->addSql('ALTER TABLE patient_allergies DROP CONSTRAINT FK_2B926D24DBFD579D');
        $this->addSql('DROP TABLE allergies');
        $this->addSql('DROP TABLE diet_days');
        $this->addSql('DROP TABLE dietary_plans');
        $this->addSql('DROP TABLE dietary_plan_document_chunks');
        $this->addSql('DROP TABLE food_items');
        $this->addSql('DROP TABLE meal_items');
        $this->addSql('DROP TABLE meals');
        $this->addSql('DROP TABLE measurements');
        $this->addSql('DROP TABLE nutritionist_profiles');
        $this->addSql('DROP TABLE patient_allergies');
        $this->addSql('DROP TABLE users');
        $this->addSql('ALTER TABLE document_chunks DROP metadata');
        $this->addSql('ALTER TABLE document_chunks DROP deleted_at');
        $this->addSql('ALTER TABLE document_chunks ALTER embedding TYPE vector');
        $this->addSql('ALTER TABLE patients DROP CONSTRAINT FK_2CCC2E2CBC1EF62C');
        $this->addSql('DROP INDEX IDX_2CCC2E2CBC1EF62C');
        $this->addSql('ALTER TABLE patients DROP deleted_at');
        $this->addSql('ALTER TABLE patients DROP nutritionist_profile_id');
    }
}
