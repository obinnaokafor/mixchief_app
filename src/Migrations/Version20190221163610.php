<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190221163610 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE supply_item ADD stock_id INT DEFAULT NULL, DROP stock');
        $this->addSql('ALTER TABLE supply_item ADD CONSTRAINT FK_A4F34107DCD6110 FOREIGN KEY (stock_id) REFERENCES stock (id)');
        $this->addSql('CREATE INDEX IDX_A4F34107DCD6110 ON supply_item (stock_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE supply_item DROP FOREIGN KEY FK_A4F34107DCD6110');
        $this->addSql('DROP INDEX IDX_A4F34107DCD6110 ON supply_item');
        $this->addSql('ALTER TABLE supply_item ADD stock INT NOT NULL, DROP stock_id');
    }
}
