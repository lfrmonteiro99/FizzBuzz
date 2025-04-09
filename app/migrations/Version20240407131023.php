<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240407131023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create FizzBuzzRequest table';
    }

    public function up(Schema $schema): void
    {
        // Drop table if it exists
        $this->addSql('DROP TABLE IF EXISTS fizz_buzz_requests');

        $this->addSql('CREATE TABLE fizz_buzz_requests (
            id INT AUTO_INCREMENT NOT NULL,
            limit_value INT NOT NULL,
            `int1` INT NOT NULL,
            `int2` INT NOT NULL,
            str1 VARCHAR(255) NOT NULL,
            str2 VARCHAR(255) NOT NULL,
            hits INT NOT NULL DEFAULT 0,
            version INT NOT NULL DEFAULT 1,
            tracking_state VARCHAR(20) NOT NULL DEFAULT \'pending\',
            created_at DATETIME NOT NULL,
            processed_at DATETIME DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE UNIQUE INDEX unique_request ON fizz_buzz_requests (limit_value, `int1`, `int2`, str1, str2)');
        $this->addSql('CREATE INDEX idx_hits_created ON fizz_buzz_requests (hits, created_at)');
        $this->addSql('CREATE INDEX idx_created_processed ON fizz_buzz_requests (created_at, processed_at)');
        $this->addSql('CREATE INDEX idx_processed_hits ON fizz_buzz_requests (processed_at, hits)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS fizz_buzz_requests');
    }
} 