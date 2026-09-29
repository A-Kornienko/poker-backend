<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260916100015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create user test and test1';
    }

    public function up(Schema $schema): void
    {
        //@12345Qq
        $password = '$2y$13$QsQJ5xg5VW8Lx92JG9oKIOlFqNNB0dM1f69A/5.LGDbs9p6NRnFma';

        $this->addSql("INSERT INTO `user`(
                   `login`,
                   `email`,
                   `password`,
                   `balance`,
                   `avatar`,
                   `last_login`,
                   `created_at`,
                   `updated_at`, 
                   `role`
                   ) VALUES 
                   ('test', 'test@test.com', '" . $password . "', 100.00, '', 0, UNIX_TIMESTAMP(NOW()), UNIX_TIMESTAMP(NOW()), 'ROLE_PLAYER'),
                   ('test1', 'test1@test.com', '" . $password . "', 100.00, '', 0, UNIX_TIMESTAMP(NOW()), UNIX_TIMESTAMP(NOW()), 'ROLE_PLAYER');");

    }

    public function down(Schema $schema): void
    {
    }
}
