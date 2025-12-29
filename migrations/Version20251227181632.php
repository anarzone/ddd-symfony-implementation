<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251227181632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE api_token (
                id INT AUTO_INCREMENT NOT NULL,
                token VARCHAR(64) NOT NULL,
                description VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                expires_at DATETIME DEFAULT NULL,
                last_used_at DATETIME DEFAULT NULL,
                user_id INT NOT NULL,
                UNIQUE INDEX UNIQ_7BA2F5EB5F37A13B (token),
                INDEX IDX_7BA2F5EBA76ED395 (user_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
    ');

        $this->addSql('
            CREATE TABLE inventory_reservations (
                id INT AUTO_INCREMENT NOT NULL,
                quantity INT NOT NULL,
                order_reference VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                expires_at DATETIME NOT NULL,
                status VARCHAR(20) NOT NULL,
                stock_id INT NOT NULL,
                user_id INT NOT NULL,
                INDEX IDX_BE28F24DCD6110 (stock_id),
                INDEX IDX_BE28F24A76ED395 (user_id),
                INDEX idx_reservation_expiry (expires_at),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
    ');

        $this->addSql('
            CREATE TABLE inventory_stocks (
                id INT AUTO_INCREMENT NOT NULL,
                total_quantity INT NOT NULL,
                created_at DATETIME NOT NULL,
                sku_code VARCHAR(50) NOT NULL,
                sku_name VARCHAR(255) NOT NULL,
                warehouse_id INT NOT NULL,
                INDEX IDX_562B04AE5080ECDE (warehouse_id),
                INDEX idx_stock_sku (sku_code),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
    ');

        $this->addSql('
            CREATE TABLE inventory_warehouses (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(100) NOT NULL,
                capacity INT NOT NULL,
                is_active TINYINT NOT NULL,
                type VARCHAR(20) NOT NULL,
                created_at DATETIME NOT NULL,
                address VARCHAR(100) NOT NULL,
                city VARCHAR(50) NOT NULL,
                postal_code VARCHAR(10) NOT NULL,
                latitude NUMERIC(10, 8) DEFAULT NULL,
                longitude NUMERIC(11, 8) DEFAULT NULL,
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
    ');

        $this->addSql('
            CREATE TABLE user (
                id INT AUTO_INCREMENT NOT NULL,
                email VARCHAR(180) NOT NULL,
                roles JSON NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
                INDEX idx_user_email (email),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
    ');

        $this->addSql('
            ALTER TABLE api_token
            ADD CONSTRAINT FK_7BA2F5EBA76ED395
            FOREIGN KEY (user_id) REFERENCES user (id)
            ON DELETE CASCADE
    ');

        $this->addSql('
            ALTER TABLE inventory_reservations
            ADD CONSTRAINT FK_BE28F24DCD6110
            FOREIGN KEY (stock_id) REFERENCES inventory_stocks (id)
            ON DELETE CASCADE
    ');

        $this->addSql('
            ALTER TABLE inventory_reservations
            ADD CONSTRAINT FK_BE28F24A76ED395
            FOREIGN KEY (user_id) REFERENCES user (id)
            ON DELETE CASCADE
    ');

        $this->addSql('
            ALTER TABLE inventory_stocks
            ADD CONSTRAINT FK_562B04AE5080ECDE
            FOREIGN KEY (warehouse_id) REFERENCES inventory_warehouses (id)
    ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_token DROP FOREIGN KEY FK_7BA2F5EBA76ED395');
        $this->addSql('ALTER TABLE inventory_reservations DROP FOREIGN KEY FK_BE28F24DCD6110');
        $this->addSql('ALTER TABLE inventory_reservations DROP FOREIGN KEY FK_BE28F24A76ED395');
        $this->addSql('ALTER TABLE inventory_stocks DROP FOREIGN KEY FK_562B04AE5080ECDE');
        $this->addSql('DROP TABLE api_token');
        $this->addSql('DROP TABLE inventory_reservations');
        $this->addSql('DROP TABLE inventory_stocks');
        $this->addSql('DROP TABLE inventory_warehouses');
        $this->addSql('DROP TABLE user');
    }
}
