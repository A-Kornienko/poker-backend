<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create normalized cash table history model, preserve board cards, and record bet types.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `table_history_player` (id INT AUTO_INCREMENT NOT NULL, table_history_id INT NOT NULL, place INT DEFAULT 0 NOT NULL, seat INT DEFAULT 0 NOT NULL, login VARCHAR(70) NOT NULL, cards JSON DEFAULT NULL, stack_before NUMERIC(10, 2) DEFAULT 0 NOT NULL, stack_after NUMERIC(10, 2) DEFAULT 0 NOT NULL, net_change NUMERIC(10, 2) DEFAULT 0 NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, is_folded TINYINT(1) DEFAULT 0 NOT NULL, is_all_in TINYINT(1) DEFAULT 0 NOT NULL, created_at INT DEFAULT 0 NOT NULL, updated_at INT DEFAULT 0 NOT NULL, INDEX IDX_9F7B907F4F3F3D70 (table_history_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `table_history_player` ADD CONSTRAINT FK_9F7B907F4F3F3D70 FOREIGN KEY (table_history_id) REFERENCES `table_history` (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE `table_history_action` (id INT AUTO_INCREMENT NOT NULL, table_history_id INT NOT NULL, player VARCHAR(70) NOT NULL, seat INT DEFAULT 0 NOT NULL, round VARCHAR(255) NOT NULL, action_type VARCHAR(255) NOT NULL, bet_type VARCHAR(255) DEFAULT NULL, amount NUMERIC(10, 2) DEFAULT NULL, sequence_number INT DEFAULT 0 NOT NULL, created_at INT DEFAULT 0 NOT NULL, updated_at INT DEFAULT 0 NOT NULL, INDEX IDX_6ECF876F4F3F3D70 (table_history_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `table_history_action` ADD CONSTRAINT FK_6ECF876F4F3F3D70 FOREIGN KEY (table_history_id) REFERENCES `table_history` (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE `table_history_pot` (id INT AUTO_INCREMENT NOT NULL, table_history_id INT NOT NULL, round VARCHAR(255) NOT NULL, type VARCHAR(255) DEFAULT "main" NOT NULL, amount NUMERIC(10, 2) DEFAULT 0 NOT NULL, created_at INT DEFAULT 0 NOT NULL, updated_at INT DEFAULT 0 NOT NULL, INDEX IDX_0D5D3A764F3F3D70 (table_history_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `table_history_pot` ADD CONSTRAINT FK_0D5D3A764F3F3D70 FOREIGN KEY (table_history_id) REFERENCES `table_history` (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE `table_history_winner` (id INT AUTO_INCREMENT NOT NULL, table_history_id INT NOT NULL, player VARCHAR(70) NOT NULL, seat INT DEFAULT 0 NOT NULL, amount_won NUMERIC(10, 2) DEFAULT 0 NOT NULL, hand_rank VARCHAR(128) DEFAULT NULL, combination JSON DEFAULT NULL, hand_cards JSON DEFAULT NULL, created_at INT DEFAULT 0 NOT NULL, updated_at INT DEFAULT 0 NOT NULL, INDEX IDX_09D8D5D84F3F3D70 (table_history_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `table_history_winner` ADD CONSTRAINT FK_09D8D5D84F3F3D70 FOREIGN KEY (table_history_id) REFERENCES `table_history` (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE `table_history_result` (id INT AUTO_INCREMENT NOT NULL, table_history_id INT NOT NULL, player VARCHAR(70) NOT NULL, seat INT DEFAULT 0 NOT NULL, delta_chips NUMERIC(10, 2) DEFAULT 0 NOT NULL, final_stack NUMERIC(10, 2) DEFAULT 0 NOT NULL, created_at INT DEFAULT 0 NOT NULL, updated_at INT DEFAULT 0 NOT NULL, INDEX IDX_8A5B9A4F4F3F3D70 (table_history_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `table_history_result` ADD CONSTRAINT FK_8A5B9A4F4F3F3D70 FOREIGN KEY (table_history_id) REFERENCES `table_history` (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE `table_history` ADD hand_number INT DEFAULT 1 NOT NULL, ADD game_type VARCHAR(255) DEFAULT "cash" NOT NULL, ADD status VARCHAR(255) DEFAULT "started" NOT NULL, ADD dealer_seat INT DEFAULT 1 NOT NULL, ADD small_blind_place INT DEFAULT 0 NOT NULL, ADD big_blind_place INT DEFAULT 0 NOT NULL, ADD small_blind NUMERIC(10, 2) DEFAULT 0.1 NOT NULL, ADD big_blind NUMERIC(10, 2) DEFAULT 0.2 NOT NULL, ADD started_at INT DEFAULT 0 NOT NULL, ADD ended_at INT DEFAULT NULL, CHANGE cards board_cards JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE `table_history` DROP players, DROP blinds, DROP dealer, DROP preflop, DROP flop, DROP turn, DROP river, DROP pot, DROP winners');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE `table_history_result`');
        $this->addSql('DROP TABLE `table_history_winner`');
        $this->addSql('DROP TABLE `table_history_pot`');
        $this->addSql('DROP TABLE `table_history_action`');
        $this->addSql('DROP TABLE `table_history_player`');

        $tableHistorySchema = $this->connection->createSchemaManager()->introspectTable('table_history');
        $cardsColumnSql = $tableHistorySchema->hasColumn('board_cards')
            ? 'CHANGE board_cards cards JSON DEFAULT NULL'
            : 'ADD cards JSON DEFAULT NULL';

        $this->addSql('ALTER TABLE `table_history` ADD players JSON NOT NULL, ADD blinds JSON NOT NULL, ADD dealer INT NOT NULL, ADD preflop JSON DEFAULT NULL, ADD flop JSON DEFAULT NULL, ADD turn JSON DEFAULT NULL, ADD river JSON DEFAULT NULL, ADD pot JSON DEFAULT NULL, ADD winners JSON DEFAULT NULL, ' . $cardsColumnSql);
        $this->addSql('ALTER TABLE `table_history` DROP hand_number, DROP game_type, DROP status, DROP dealer_seat, DROP small_blind_place, DROP big_blind_place, DROP small_blind, DROP big_blind, DROP started_at, DROP ended_at');
    }
}
