<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rename int1 and int2 columns to divisor1 and divisor2
 */
final class Version20250408000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename int1 and int2 columns to divisor1 and divisor2';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fizz_buzz_requests CHANGE `int1` divisor1 INT NOT NULL');
        $this->addSql('ALTER TABLE fizz_buzz_requests CHANGE `int2` divisor2 INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE fizz_buzz_requests CHANGE divisor1 `int1` INT NOT NULL');
        $this->addSql('ALTER TABLE fizz_buzz_requests CHANGE divisor2 `int2` INT NOT NULL');
    }
} 