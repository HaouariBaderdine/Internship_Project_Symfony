<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230922210215 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE item DROP name, DROP price, DROP unit_code, DROP unit_description, DROP vat_amount, DROP vat_percentage, CHANGE id id VARCHAR(255) NOT NULL, CHANGE description item_description LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993986D7914CF');
        $this->addSql('ALTER TABLE `order` CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', CHANGE deliver_to_id deliver_to_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993986D7914CF FOREIGN KEY (deliver_to_id) REFERENCES contact (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_line DROP FOREIGN KEY FK_9CE58EE1126F525E');
        $this->addSql('DROP INDEX IDX_9CE58EE1126F525E ON order_line');
        $this->addSql('ALTER TABLE order_line DROP item_id, CHANGE id id VARCHAR(255) NOT NULL, CHANGE order_id order_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE item ADD name VARCHAR(255) NOT NULL, ADD price NUMERIC(10, 2) NOT NULL, ADD unit_code VARCHAR(10) NOT NULL, ADD unit_description VARCHAR(255) NOT NULL, ADD vat_amount NUMERIC(10, 2) NOT NULL, ADD vat_percentage NUMERIC(5, 2) NOT NULL, CHANGE id id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', CHANGE item_description description LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993986D7914CF');
        $this->addSql('ALTER TABLE `order` CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE deliver_to_id deliver_to_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993986D7914CF FOREIGN KEY (deliver_to_id) REFERENCES contact (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE order_line ADD item_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary_ordered_time)\', CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE order_id order_id INT NOT NULL');
        $this->addSql('ALTER TABLE order_line ADD CONSTRAINT FK_9CE58EE1126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_9CE58EE1126F525E ON order_line (item_id)');
    }
}
