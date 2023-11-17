<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231111183809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // $this->addSql('DROP INDEX email ON customer');
        // $this->addSql('ALTER TABLE item_part DROP FOREIGN KEY item_foreign_key');
        // $this->addSql('ALTER TABLE item_part DROP FOREIGN KEY stock_foreign_key');
        // $this->addSql('DROP INDEX item_foreign_key ON item_part');
        // $this->addSql('DROP INDEX stock_foreign_key ON item_part');
        // $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY item_foreign_key_order_item');
        // $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY order_foreign_key');
        // $this->addSql('DROP INDEX item_foreign_key_order_item ON order_item');
        // $this->addSql('DROP INDEX order_foreign_key ON order_item');
        $this->addSql('ALTER TABLE orders ADD payment_reference VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX email ON customer (email)');
        $this->addSql('ALTER TABLE item_part ADD CONSTRAINT item_foreign_key FOREIGN KEY (item_id) REFERENCES item (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE item_part ADD CONSTRAINT stock_foreign_key FOREIGN KEY (stock_id) REFERENCES stock (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX item_foreign_key ON item_part (item_id)');
        $this->addSql('CREATE INDEX stock_foreign_key ON item_part (stock_id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT item_foreign_key_order_item FOREIGN KEY (item_id) REFERENCES item (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT order_foreign_key FOREIGN KEY (order_id) REFERENCES orders (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX item_foreign_key_order_item ON order_item (item_id)');
        $this->addSql('CREATE INDEX order_foreign_key ON order_item (order_id)');
        $this->addSql('ALTER TABLE orders DROP payment_reference');
        $this->addSql('ALTER TABLE supply_item ADD CONSTRAINT supply_foreign_key FOREIGN KEY (supply_id) REFERENCES supply (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX supply_foreign_key ON supply_item (supply_id)');
    }
}
