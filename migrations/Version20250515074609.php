<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250515074609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE chauffeur_chauffeur (chauffeur_source INT NOT NULL, chauffeur_target INT NOT NULL, INDEX IDX_5C25DE22E0D857D2 (chauffeur_source), INDEX IDX_5C25DE22F93D075D (chauffeur_target), PRIMARY KEY(chauffeur_source, chauffeur_target)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE chauffeur_chauffeur ADD CONSTRAINT FK_5C25DE22E0D857D2 FOREIGN KEY (chauffeur_source) REFERENCES chauffeur (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE chauffeur_chauffeur ADD CONSTRAINT FK_5C25DE22F93D075D FOREIGN KEY (chauffeur_target) REFERENCES chauffeur (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE chauffeur_chauffeur DROP FOREIGN KEY FK_5C25DE22E0D857D2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE chauffeur_chauffeur DROP FOREIGN KEY FK_5C25DE22F93D075D
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE chauffeur_chauffeur
        SQL);
    }
}
