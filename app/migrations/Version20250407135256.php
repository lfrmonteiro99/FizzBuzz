<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250407135256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX unique_request ON fizz_buzz_requests
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE fizz_buzz_requests ADD start INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX unique_request ON fizz_buzz_requests (start, limit_value, `int1`, `int2`, str1, str2)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX unique_request ON fizz_buzz_requests
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE fizz_buzz_requests DROP start
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX unique_request ON fizz_buzz_requests (limit_value, `int1`, `int2`, str1, str2)
        SQL);
    }
}
