<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230921213711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, account_name VARCHAR(255) NOT NULL, address_line1 VARCHAR(255) NOT NULL, address_line2 VARCHAR(255) DEFAULT NULL, city VARCHAR(255) NOT NULL, contact_name VARCHAR(255) NOT NULL, country VARCHAR(2) NOT NULL, zip_code VARCHAR(10) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE item (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, price NUMERIC(10, 2) NOT NULL, unit_code VARCHAR(10) NOT NULL, unit_description VARCHAR(255) NOT NULL, vat_amount NUMERIC(10, 2) NOT NULL, vat_percentage NUMERIC(5, 2) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, deliver_to_id INT DEFAULT NULL, order_number INT NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, INDEX IDX_F52993986D7914CF (deliver_to_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_line (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, item_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', amount DOUBLE PRECISION NOT NULL, description VARCHAR(255) NOT NULL, discount DOUBLE PRECISION NOT NULL, quantity INT NOT NULL, unit_code VARCHAR(255) NOT NULL, unit_description VARCHAR(255) NOT NULL, unit_price DOUBLE PRECISION NOT NULL, vat_amount DOUBLE PRECISION NOT NULL, vat_percentage DOUBLE PRECISION NOT NULL, INDEX IDX_9CE58EE18D9F6D38 (order_id), INDEX IDX_9CE58EE1126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993986D7914CF FOREIGN KEY (deliver_to_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE order_line ADD CONSTRAINT FK_9CE58EE18D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE order_line ADD CONSTRAINT FK_9CE58EE1126F525E FOREIGN KEY (item_id) REFERENCES item (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993986D7914CF');
        $this->addSql('ALTER TABLE order_line DROP FOREIGN KEY FK_9CE58EE18D9F6D38');
        $this->addSql('ALTER TABLE order_line DROP FOREIGN KEY FK_9CE58EE1126F525E');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE item');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE order_line');
    }

    public function isTransactional(): bool
    {
        return false;
    }

}
