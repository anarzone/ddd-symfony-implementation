<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251230181334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Drop all foreign key constraints
        $this->addSql('ALTER TABLE api_token DROP FOREIGN KEY FK_7BA2F5EBA76ED395');
        $this->addSql('ALTER TABLE inventory_reservations DROP FOREIGN KEY FK_BE28F24DCD6110');
        $this->addSql('ALTER TABLE inventory_reservations DROP FOREIGN KEY FK_BE28F24A76ED395');
        $this->addSql('ALTER TABLE inventory_stocks DROP FOREIGN KEY FK_562B04AE5080ECDE');

        // Convert ID columns to BINARY(16) for UUID storage
        $this->addSql('ALTER TABLE user CHANGE id id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE inventory_warehouses CHANGE id id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE inventory_stocks CHANGE id id BINARY(16) NOT NULL, CHANGE warehouse_id warehouse_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE inventory_reservations CHANGE id id BINARY(16) NOT NULL, CHANGE stock_id stock_id BINARY(16) NOT NULL, CHANGE user_id user_id BINARY(16) NOT NULL');
        $this->addSql('ALTER TABLE api_token CHANGE id id BINARY(16) NOT NULL, CHANGE user_id user_id BINARY(16) NOT NULL');

        // Recreate foreign key constraints
        $this->addSql('ALTER TABLE api_token ADD CONSTRAINT FK_7BA2F5EBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inventory_reservations ADD CONSTRAINT FK_BE28F24DCD6110 FOREIGN KEY (stock_id) REFERENCES inventory_stocks (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inventory_reservations ADD CONSTRAINT FK_BE28F24A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inventory_stocks ADD CONSTRAINT FK_562B04AE5080ECDE FOREIGN KEY (warehouse_id) REFERENCES inventory_warehouses (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_token CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE inventory_reservations CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE stock_id stock_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE inventory_stocks CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE warehouse_id warehouse_id INT NOT NULL');
        $this->addSql('ALTER TABLE inventory_warehouses CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE id id INT AUTO_INCREMENT NOT NULL');
    }
}
