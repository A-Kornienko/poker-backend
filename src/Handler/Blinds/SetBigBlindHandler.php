<?php

declare(strict_types=1);

namespace App\Handler\Blinds;

use App\Entity\Table;
use App\Enum\{BetType, TableUserStatus};
use App\Helper\Calculator;
use Doctrine\ORM\EntityManagerInterface;

class SetBigBlindHandler
{
    public function __construct(
        protected EntityManagerInterface $entityManager
    ) {
    }

    /**
     * По правилам когда за столом 2 игрока, большой блайнд у первого игрока после диллера.
     */
    protected function setBigBlindForTwoPlayers(Table $table, array $activeTableUsersSortedByPlace): Table
    {
        unset($activeTableUsersSortedByPlace[$table->getDealerPlace()]);
        $firstPlaceNumber = (int) array_key_first($activeTableUsersSortedByPlace);
        $currentTableUser = $activeTableUsersSortedByPlace[$firstPlaceNumber];

        // Set big blind bet, if not enough chips - go all-in
        $bigBlindBet = min($currentTableUser->getStack(), $table->getBigBlind());
        $stack       = Calculator::subtract($currentTableUser->getStack(), $bigBlindBet);

        $currentTableUser->setStack($stack)
            ->setBet($bigBlindBet)
            ->setBetType($stack <= 0 ? BetType::AllIn : BetType::BigBlind);

        $table->setBigBlindPlace($firstPlaceNumber);
        $this->entityManager->persist($currentTableUser);

        return $table;
    }

    protected function setBigBlindDefault(Table $table, array $activeTableUsersSortedByPlace): Table
    {
        // Get player places
        $playerPlaces = array_keys($activeTableUsersSortedByPlace);
        // Determine the index of the small blind place
        $smallBlindPlaceIndex = array_search($table->getSmallBlindPlace(), $playerPlaces);
        // Get the first place number in the list of active players
        $firstPlaceNumber = array_key_first($activeTableUsersSortedByPlace);
        // Get whether the small blind place is not the last among active players.
        $isBigBlindPlaceNotLast = $smallBlindPlaceIndex !== false && array_key_exists($smallBlindPlaceIndex + 1, $playerPlaces);
        // Determine the next place after the small blind, which will be the big blind.
        $bigBlindPlace = $isBigBlindPlaceNotLast ? $playerPlaces[$smallBlindPlaceIndex + 1] : $firstPlaceNumber;
        // Set values for the user and the table.
        $currentTableUser = $activeTableUsersSortedByPlace[$bigBlindPlace];

        // If not enough chips - go all-in
        $bigBlindBet           = min($currentTableUser->getStack(), $table->getBigBlind());
        $currentTableUserChips = Calculator::subtract($currentTableUser->getStack(), $bigBlindBet);

        $currentTableUser->setStack($currentTableUserChips)
            ->setBet($bigBlindBet)
            ->setBetType($currentTableUserChips <= 0 ? BetType::AllIn : BetType::BigBlind);

        if ($currentTableUser->getStatus() === TableUserStatus::WaitingBB) {
            $currentTableUser->setStatus(TableUserStatus::Active);
        }

        $table->setBigBlindPlace($bigBlindPlace);
        $this->entityManager->persist($currentTableUser);

        return $table;
    }

    public function __invoke(Table $table, array $activeTableUsersSortedByPlace): Table
    {
        if (!$table->getSmallBlindPlace()) {
            return $table;
        }

        $bigBlind = $table->getTournament() ? $table->getTournament()->getBigBlind() : $table->getBigBlind();
        $table->getSetting()->setBigBlind($bigBlind);

        return count($activeTableUsersSortedByPlace) < 3
            ? $this->setBigBlindForTwoPlayers($table, $activeTableUsersSortedByPlace)
            : $this->setBigBlindDefault($table, $activeTableUsersSortedByPlace);
    }
}
