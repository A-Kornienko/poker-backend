<?php

declare(strict_types=1);

namespace App\Handler\TableState;

use App\Entity\Table;
use App\Enum\Round;
use App\Handler\Bank\CalculateBankHandler;
use App\Handler\Cards\SetTableCardsHandler;
use App\Repository\TableUserRepository;
use App\Service\PlayerService;
use Doctrine\ORM\EntityManagerInterface;

class RoundHandler
{
    public function __construct(
        protected PlayerService $playerService,
        protected CalculateBankHandler $calculateBankHandler,
        protected TableUserRepository $tableUserRepository,
        protected SetTableCardsHandler $setTableCardsHandler,
        protected EntityManagerInterface $entityManager,
        protected TurnHandler $turnHandler
    ) {
    }

    public function startRound(Table $table): void
    {
        $this->playerService->preparePlayersToNewRound($table->getTableUsers()->toArray());

        // Get the table rake status and calculate the bank.
        $table->setRakeStatus(true);
        ($this->calculateBankHandler)($table);

        // Get the list of active players sorted and grouped by seat number.
        $activePlayersSortedByPlace = $this->tableUserRepository->getPlayersSortedByPlace($table);

        // If all players except the last one are folding, start a fast finish round
        if ($this->isFastFinishRoundStarted($table, $activePlayersSortedByPlace)) {
            return;
        }

        $table = ($this->setTableCardsHandler)($table, $table->getRound()->countTableCards());
        // If the remaining players are all-in except the last one, start changing rounds
        $activePlayers = $this->playerService->excludeSilentPlayers($activePlayersSortedByPlace);
        $maxBet        = $this->tableUserRepository->getMaxBet($table);
        if ($this->isNextRoundStarted($table, $activePlayers, $maxBet)) {
            return;
        }

        $table = $this->turnHandler->setFirstTurn($table, $activePlayersSortedByPlace);

        $this->entityManager->persist($table);
        $this->entityManager->flush();
    }

    public function isFastFinishRoundStarted(Table $table, array $activePlayers): bool
    {
        if (count($activePlayers) < 2) {
            $table->setRound(Round::FastFinish);
            $this->entityManager->persist($table);
            $this->entityManager->flush();

            return true;
        }

        return false;
    }

    public function isNextRoundStarted(Table $table, array $activePlayers, float $maxBet): bool
    {
        if (in_array($table->getRound()->value, [Round::FastFinish->value, Round::ShowDown->value], true)) {
            $this->entityManager->persist($table);
            $this->entityManager->flush();
            return true;
        }

        $countActivePlayers = count($activePlayers);
        $currentPlayer      = current($activePlayers);
        $status             = $countActivePlayers < 1 || ($countActivePlayers === 1 && $currentPlayer->getBet() === $maxBet);

        if ($status) {
            $table->setRound($table->getRound()->next());
            $this->entityManager->persist($table);
            $this->entityManager->flush();
            $this->startRound($table);
        }

        return $status;
    }
}
