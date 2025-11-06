<?php

declare(strict_types=1);

namespace App\Handler\Blinds;

use App\Entity\Table;
use App\Enum\BetType;
use App\Helper\Calculator;
use Doctrine\ORM\EntityManagerInterface;

class SetSmallBlindHandler
{
    public function __construct(
        protected EntityManagerInterface $entityManager
    ) {
    }

    /**
     * По правилам когда за столом 2 игрока, у кого диллер, у того и малый блайнд.
     */
    protected function setSmallBlindForTwoPlayers(Table $table, array $activePlayersSortedByPlace): Table
    {
        $place         = $table->getDealerPlace();
        $currentPlayer = $activePlayersSortedByPlace[$place];

        // If not enough chips - go all-in
        $smallBlindBet = min($currentPlayer->getStack(), $table->getSmallBlind());
        $stack         = Calculator::subtract($currentPlayer->getStack(), $smallBlindBet);

        $activePlayersSortedByPlace[$place]
            ->setStack($stack)
            ->setBet($smallBlindBet)
            ->setBetType($stack <= 0 ? BetType::AllIn : BetType::SmallBlind);

        $table->setSmallBlindPlace($table->getDealerPlace());
        $this->entityManager->persist($currentPlayer);

        return $table;
    }

    /**
     * По правилам когда за столом больше 2 игроков, малый блайнд ставит первый игрок после диллера.
     */
    protected function setSmallBlindDefault(Table $table, array $activePlayersSortedByPlace): Table
    {
        // Get player seats
        $playerPlaces = array_keys($activePlayersSortedByPlace);
        // Get the index of the dealer's place
        $dealerPlaceIndex = array_search($table->getDealerPlace(), $playerPlaces);
        // Get the first place number of active players
        $firstPlaceNumber = array_key_first($activePlayersSortedByPlace);
        // Get whether the dealer's place is not the last among active players.
        $isSmallBlindPlaceNotLast = $dealerPlaceIndex !== false && array_key_exists($dealerPlaceIndex + 1, $playerPlaces);
        // Determine the next seat after the dealer, which will be the small blind.
        $smallBlindPlace = $isSmallBlindPlaceNotLast ? $playerPlaces[$dealerPlaceIndex + 1] : $firstPlaceNumber;
        // Set values for the player and the table.
        $currentPlayer = $activePlayersSortedByPlace[$smallBlindPlace];

        // If not enough chips - go all-in
        $smallBlindBet      = min($currentPlayer->getStack(), $table->getSmallBlind());
        $currentPlayerChips = Calculator::subtract($currentPlayer->getStack(), $smallBlindBet);

        $currentPlayer->setStack($currentPlayerChips)
            ->setBet($smallBlindBet)
            ->setBetType($currentPlayerChips <= 0 ? BetType::AllIn : BetType::SmallBlind);

        $table->setSmallBlindPlace($smallBlindPlace);
        $this->entityManager->persist($currentPlayer);

        return $table;
    }

    public function __invoke(Table $table, array $activePlayersSortedByPlace): Table
    {
        if (!$table->getDealerPlace()) {
            return $table;
        }

        $smallBlind = $table->getTournament() ? $table->getTournament()->getSmallBlind() : $table->getSmallBlind();
        $table->getSetting()->setSmallBlind($smallBlind);

        return count($activePlayersSortedByPlace) < 3
            ? $this->setSmallBlindForTwoPlayers($table, $activePlayersSortedByPlace)
            : $this->setSmallBlindDefault($table, $activePlayersSortedByPlace);
    }
}
