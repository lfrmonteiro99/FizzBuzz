<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250407130859 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE fizz_buzz_requests ADD updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE hits hits INT NOT NULL, CHANGE tracking_state tracking_state VARCHAR(20) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE processed_at processed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE fizz_buzz_requests DROP updated_at, CHANGE hits hits INT DEFAULT 0 NOT NULL, CHANGE tracking_state tracking_state VARCHAR(20) DEFAULT 'pending' NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE processed_at processed_at DATETIME DEFAULT NULL
        SQL);
    }
}
