<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250623144340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ride ADD chauffeur_accepteur_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride ADD CONSTRAINT FK_9B3D7CD0248D55E8 FOREIGN KEY (chauffeur_accepteur_id) REFERENCES chauffeur (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9B3D7CD0248D55E8 ON ride (chauffeur_accepteur_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ride DROP FOREIGN KEY FK_9B3D7CD0248D55E8
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_9B3D7CD0248D55E8 ON ride
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ride DROP chauffeur_accepteur_id
        SQL);
    }
}
