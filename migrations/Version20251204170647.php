<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204170647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ride ADD groupe_id INT DEFAULT NULL, ADD visibility VARCHAR(20) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride ADD CONSTRAINT FK_9B3D7CD07A45358C FOREIGN KEY (groupe_id) REFERENCES groupe (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9B3D7CD07A45358C ON ride (groupe_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ride DROP FOREIGN KEY FK_9B3D7CD07A45358C
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_9B3D7CD07A45358C ON ride
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride DROP groupe_id, DROP visibility
        SQL);
    }
}
