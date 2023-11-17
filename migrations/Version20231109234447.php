<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231109234447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_delivery (id INT AUTO_INCREMENT NOT NULL, orders_id INT NOT NULL, fullname VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, landmark VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, dispatch VARCHAR(255) DEFAULT NULL, delivered_time DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_D6790EA1CFFE9AD6 (orders_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE order_delivery ADD CONSTRAINT FK_D6790EA1CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES orders (id)');
        // $this->addSql('DROP INDEX email ON customer');
        // $this->addSql('ALTER TABLE customer CHANGE email email VARCHAR(255) NOT NULL');
        // $this->addSql('ALTER TABLE item_part DROP FOREIGN KEY item_foreign_key');
        // $this->addSql('ALTER TABLE item_part DROP FOREIGN KEY stock_foreign_key');
        // $this->addSql('DROP INDEX item_foreign_key ON item_part');
        // $this->addSql('DROP INDEX stock_foreign_key ON item_part');
        // $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY item_foreign_key_order_item');
        // $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY order_foreign_key');
        // $this->addSql('DROP INDEX item_foreign_key_order_item ON order_item');
        // $this->addSql('DROP INDEX order_foreign_key ON order_item');
        // $this->addSql('ALTER TABLE orders DROP FOREIGN KEY customer_email_foreign_key');
        // $this->addSql('DROP INDEX customer_email_foreign_key ON orders');
        // $this->addSql('ALTER TABLE supply_item DROP FOREIGN KEY supply_foreign_key');
        // $this->addSql('DROP INDEX supply_foreign_key ON supply_item');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_delivery DROP FOREIGN KEY FK_D6790EA1CFFE9AD6');
        $this->addSql('DROP TABLE order_delivery');
        // $this->addSql('ALTER TABLE customer CHANGE email email VARCHAR(255) DEFAULT NULL');
        // $this->addSql('CREATE UNIQUE INDEX email ON customer (email)');
        // $this->addSql('ALTER TABLE item_part ADD CONSTRAINT item_foreign_key FOREIGN KEY (item_id) REFERENCES item (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        // $this->addSql('ALTER TABLE item_part ADD CONSTRAINT stock_foreign_key FOREIGN KEY (stock_id) REFERENCES stock (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        // $this->addSql('CREATE INDEX item_foreign_key ON item_part (item_id)');
        // $this->addSql('CREATE INDEX stock_foreign_key ON item_part (stock_id)');
        // $this->addSql('ALTER TABLE order_item ADD CONSTRAINT item_foreign_key_order_item FOREIGN KEY (item_id) REFERENCES item (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        // $this->addSql('ALTER TABLE order_item ADD CONSTRAINT order_foreign_key FOREIGN KEY (order_id) REFERENCES orders (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        // $this->addSql('CREATE INDEX item_foreign_key_order_item ON order_item (item_id)');
        // $this->addSql('CREATE INDEX order_foreign_key ON order_item (order_id)');
        // $this->addSql('ALTER TABLE orders ADD CONSTRAINT customer_email_foreign_key FOREIGN KEY (customer) REFERENCES customer (email) ON UPDATE NO ACTION ON DELETE NO ACTION');
        // $this->addSql('CREATE INDEX customer_email_foreign_key ON orders (customer)');
        // $this->addSql('ALTER TABLE supply_item ADD CONSTRAINT supply_foreign_key FOREIGN KEY (supply_id) REFERENCES supply (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        // $this->addSql('CREATE INDEX supply_foreign_key ON supply_item (supply_id)');
    }
}
