<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the single STI table backing all product video kinds (file/url/embed and any
 * extension kind reusing an existing column). A new kind only needs a migration if it introduces
 * a new column.
 */
final class Version20260625000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the setono_sylius_video__product_video table (Single Table Inheritance for product videos)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on a MySQL-compatible platform.",
        );

        $this->addSql("CREATE TABLE setono_sylius_video__product_video (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, position INT DEFAULT NULL, poster_path VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', type VARCHAR(20) NOT NULL, code LONGTEXT DEFAULT NULL, path VARCHAR(255) DEFAULT NULL, url VARCHAR(2048) DEFAULT NULL, INDEX IDX_2F1813254584665A (product_id), INDEX setono_sylius_video_type_idx (type), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE setono_sylius_video__product_video ADD CONSTRAINT FK_2F1813254584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on a MySQL-compatible platform.",
        );

        $this->addSql('ALTER TABLE setono_sylius_video__product_video DROP FOREIGN KEY FK_2F1813254584665A');
        $this->addSql('DROP TABLE setono_sylius_video__product_video');
    }
}
