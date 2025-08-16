<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250611122808 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE course ADD client_contact VARCHAR(30) NOT NULL, ADD passagers VARCHAR(10) NOT NULL, ADD bagages INT NOT NULL, ADD vehicule VARCHAR(50) NOT NULL, ADD booster_seat INT NOT NULL, ADD baby_seat INT NOT NULL, ADD comment LONGTEXT DEFAULT NULL, ADD created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE course DROP client_contact, DROP passagers, DROP bagages, DROP vehicule, DROP booster_seat, DROP baby_seat, DROP comment, DROP created_at
        SQL);
    }
}
