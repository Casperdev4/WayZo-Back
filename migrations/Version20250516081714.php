<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250516081714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE ride (id INT AUTO_INCREMENT NOT NULL, client_name VARCHAR(30) NOT NULL, client_contact VARCHAR(30) NOT NULL, depart VARCHAR(255) NOT NULL, destination VARCHAR(255) NOT NULL, date DATE NOT NULL, time TIME NOT NULL, passengers INT NOT NULL, luggage INT NOT NULL, vehicle VARCHAR(255) NOT NULL, booster_seat INT NOT NULL, baby_seat INT NOT NULL, price DOUBLE PRECISION NOT NULL, comment LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE ride
        SQL);
    }
}
