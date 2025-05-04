<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250331141839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity (id INT AUTO_INCREMENT NOT NULL, date DATETIME NOT NULL, action VARCHAR(255) NOT NULL, entity VARCHAR(20) DEFAULT NULL, user_id INT DEFAULT NULL, entity_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, user_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, email VARCHAR(255) NOT NULL, telephone VARCHAR(11) DEFAULT NULL, user_id INT DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE inventory (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, quantity BIGINT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE item (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, sellingPrice DOUBLE PRECISION NOT NULL, discount DOUBLE PRECISION NOT NULL, c_id INT NOT NULL, img VARCHAR(255) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_1F1B251E12469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE item_part (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, stock_id INT NOT NULL, portion DOUBLE PRECISION NOT NULL, user_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_delivery (id INT AUTO_INCREMENT NOT NULL, orders_id INT NOT NULL, fullname VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, landmark VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, dispatch VARCHAR(255) DEFAULT NULL, delivered_time DATETIME DEFAULT NULL, user_id INT NOT NULL, amount INT DEFAULT NULL, UNIQUE INDEX UNIQ_D6790EA1CFFE9AD6 (orders_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, order_id INT NOT NULL, discount DOUBLE PRECISION DEFAULT NULL, quantity INT NOT NULL, price DOUBLE PRECISION NOT NULL, user_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, date DATETIME NOT NULL, modified DATETIME DEFAULT NULL, amount DOUBLE PRECISION NOT NULL, discount DOUBLE PRECISION DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, payment VARCHAR(255) DEFAULT NULL, comment VARCHAR(255) DEFAULT NULL, user_id INT DEFAULT NULL, payment_reference VARCHAR(255) DEFAULT NULL, confirmed TINYINT(1) DEFAULT NULL, INDEX IDX_E52FFDEE9395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE spillage (id INT AUTO_INCREMENT NOT NULL, stock_id INT NOT NULL, reason VARCHAR(255) NOT NULL, quantity INT NOT NULL, created DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_F72DDCF4DCD6110 (stock_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stock (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, quantity DOUBLE PRECISION NOT NULL, unit VARCHAR(255) DEFAULT NULL, deleted TINYINT(1) DEFAULT NULL, user_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, email_token VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, authcode VARCHAR(255) DEFAULT NULL, start DATETIME NOT NULL, expiry DATETIME DEFAULT NULL, status TINYINT(1) NOT NULL, renew TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_A3C664D3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supplier (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, user_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supply (id INT AUTO_INCREMENT NOT NULL, supplier_id INT DEFAULT NULL, date DATETIME NOT NULL, name VARCHAR(255) DEFAULT NULL, total DOUBLE PRECISION NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_D219948C2ADD6D8C (supplier_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supply_item (id INT AUTO_INCREMENT NOT NULL, stock INT NOT NULL, supply_id INT NOT NULL, quantity INT DEFAULT NULL, amount DOUBLE PRECISION DEFAULT NULL, user_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transaction (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, user_id INT NOT NULL, amount INT NOT NULL, reference VARCHAR(255) DEFAULT NULL, paystack_auth VARCHAR(255) DEFAULT NULL, last_four INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(255) DEFAULT NULL, lname VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, verification VARCHAR(255) DEFAULT NULL, reset VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, enabled TINYINT(1) NOT NULL, address VARCHAR(255) DEFAULT NULL, state VARCHAR(255) DEFAULT NULL, company VARCHAR(255) NOT NULL, logo VARCHAR(255) DEFAULT NULL, created DATETIME DEFAULT NULL, modified DATETIME DEFAULT NULL, paystack_code VARCHAR(255) DEFAULT NULL, paystack_auth VARCHAR(255) DEFAULT NULL, api_key VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), UNIQUE INDEX UNIQ_1483A5E9450FF010 (telephone), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251E12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE order_delivery ADD CONSTRAINT FK_D6790EA1CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES orders (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE9395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE spillage ADD CONSTRAINT FK_F72DDCF4DCD6110 FOREIGN KEY (stock_id) REFERENCES stock (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE supply ADD CONSTRAINT FK_D219948C2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item DROP FOREIGN KEY FK_1F1B251E12469DE2');
        $this->addSql('ALTER TABLE order_delivery DROP FOREIGN KEY FK_D6790EA1CFFE9AD6');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE9395C3F3');
        $this->addSql('ALTER TABLE spillage DROP FOREIGN KEY FK_F72DDCF4DCD6110');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A76ED395');
        $this->addSql('ALTER TABLE supply DROP FOREIGN KEY FK_D219948C2ADD6D8C');
        $this->addSql('DROP TABLE activity');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE inventory');
        $this->addSql('DROP TABLE item');
        $this->addSql('DROP TABLE item_part');
        $this->addSql('DROP TABLE order_delivery');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE spillage');
        $this->addSql('DROP TABLE stock');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('DROP TABLE supplier');
        $this->addSql('DROP TABLE supply');
        $this->addSql('DROP TABLE supply_item');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE users');
    }
}
