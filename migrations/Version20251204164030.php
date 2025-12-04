<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204164030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE groupe (id INT AUTO_INCREMENT NOT NULL, proprietaire_id INT NOT NULL, nom VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, code VARCHAR(50) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_4B98C2177153098 (code), INDEX IDX_4B98C2176C50E4A (proprietaire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE groupe_invitation (id INT AUTO_INCREMENT NOT NULL, groupe_id INT NOT NULL, invite_par_id INT NOT NULL, chauffeur_invite_id INT DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, token VARCHAR(100) NOT NULL, status VARCHAR(20) NOT NULL, message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', responded_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_49F0507E5F37A13B (token), INDEX IDX_49F0507E7A45358C (groupe_id), INDEX IDX_49F0507E6B8B1C79 (invite_par_id), INDEX IDX_49F0507EEAC3DEDD (chauffeur_invite_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE groupe_membre (id INT AUTO_INCREMENT NOT NULL, groupe_id INT NOT NULL, chauffeur_id INT NOT NULL, invite_par_id INT DEFAULT NULL, role VARCHAR(20) NOT NULL, joined_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_9D8A07137A45358C (groupe_id), INDEX IDX_9D8A071385C0B3BE (chauffeur_id), INDEX IDX_9D8A07136B8B1C79 (invite_par_id), UNIQUE INDEX unique_membre_groupe (groupe_id, chauffeur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe ADD CONSTRAINT FK_4B98C2176C50E4A FOREIGN KEY (proprietaire_id) REFERENCES chauffeur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_invitation ADD CONSTRAINT FK_49F0507E7A45358C FOREIGN KEY (groupe_id) REFERENCES groupe (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_invitation ADD CONSTRAINT FK_49F0507E6B8B1C79 FOREIGN KEY (invite_par_id) REFERENCES chauffeur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_invitation ADD CONSTRAINT FK_49F0507EEAC3DEDD FOREIGN KEY (chauffeur_invite_id) REFERENCES chauffeur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_membre ADD CONSTRAINT FK_9D8A07137A45358C FOREIGN KEY (groupe_id) REFERENCES groupe (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_membre ADD CONSTRAINT FK_9D8A071385C0B3BE FOREIGN KEY (chauffeur_id) REFERENCES chauffeur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_membre ADD CONSTRAINT FK_9D8A07136B8B1C79 FOREIGN KEY (invite_par_id) REFERENCES chauffeur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course ADD groupe_id INT DEFAULT NULL, ADD visibility VARCHAR(20) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course ADD CONSTRAINT FK_169E6FB97A45358C FOREIGN KEY (groupe_id) REFERENCES groupe (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_169E6FB97A45358C ON course (groupe_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE course DROP FOREIGN KEY FK_169E6FB97A45358C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe DROP FOREIGN KEY FK_4B98C2176C50E4A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_invitation DROP FOREIGN KEY FK_49F0507E7A45358C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_invitation DROP FOREIGN KEY FK_49F0507E6B8B1C79
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_invitation DROP FOREIGN KEY FK_49F0507EEAC3DEDD
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_membre DROP FOREIGN KEY FK_9D8A07137A45358C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_membre DROP FOREIGN KEY FK_9D8A071385C0B3BE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE groupe_membre DROP FOREIGN KEY FK_9D8A07136B8B1C79
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE groupe
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE groupe_invitation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE groupe_membre
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_169E6FB97A45358C ON course
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course DROP groupe_id, DROP visibility
        SQL);
    }
}
