<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204172150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE facture (id INT AUTO_INCREMENT NOT NULL, emetteur_id INT NOT NULL, destinataire_id INT NOT NULL, course_id INT NOT NULL, transaction_id INT DEFAULT NULL, numero VARCHAR(50) NOT NULL, type VARCHAR(30) NOT NULL, date_emission DATE NOT NULL, date_echeance DATE DEFAULT NULL, montant_ht NUMERIC(10, 2) NOT NULL, taux_tva NUMERIC(5, 2) NOT NULL, montant_tva NUMERIC(10, 2) NOT NULL, montant_ttc NUMERIC(10, 2) NOT NULL, statut VARCHAR(20) NOT NULL, description LONGTEXT DEFAULT NULL, pdf_path VARCHAR(255) DEFAULT NULL, emetteur_info JSON NOT NULL, destinataire_info JSON NOT NULL, course_details JSON NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_FE866410F55AE19E (numero), INDEX IDX_FE86641079E92E8C (emetteur_id), INDEX IDX_FE866410A4F84F6E (destinataire_id), INDEX IDX_FE866410591CC992 (course_id), INDEX IDX_FE8664102FC0CB0F (transaction_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE facture ADD CONSTRAINT FK_FE86641079E92E8C FOREIGN KEY (emetteur_id) REFERENCES chauffeur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE facture ADD CONSTRAINT FK_FE866410A4F84F6E FOREIGN KEY (destinataire_id) REFERENCES chauffeur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE facture ADD CONSTRAINT FK_FE866410591CC992 FOREIGN KEY (course_id) REFERENCES course (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE facture ADD CONSTRAINT FK_FE8664102FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transaction ADD completed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD reference VARCHAR(50) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE facture DROP FOREIGN KEY FK_FE86641079E92E8C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE facture DROP FOREIGN KEY FK_FE866410A4F84F6E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE facture DROP FOREIGN KEY FK_FE866410591CC992
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE facture DROP FOREIGN KEY FK_FE8664102FC0CB0F
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE facture
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transaction DROP completed_at, DROP reference
        SQL);
    }
}
