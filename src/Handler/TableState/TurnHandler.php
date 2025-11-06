<?php

declare(strict_types=1);

namespace App\Handler\TableState;

use App\Entity\{Table, TableUser};
use App\Enum\{BetType, Round, TableUserStatus};
use App\Exception\ResponseException;
use App\Helper\ErrorCodeHelper;
use App\Repository\TableUserRepository;
use App\Service\PlayerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TurnHandler
{
    public const BET_EXPIRATION_TIME = 1;

    public function __construct(
        protected TableUserRepository $tableUserRepository,
        protected EntityManagerInterface $entityManager,
        protected TranslatorInterface $translator,
        protected PlayerService $playerService
    ) {
    }

    public function validateTurn(Table $table, TableUser $tableUser): ResponseException|bool
    {
        return match (true) {
            $tableUser->getBetExpirationTime() < time()                        => ResponseException::makeExceptionByCode($this->translator, ErrorCodeHelper::TIME_OVER),
            $table->getTurnPlace() !== $tableUser->getPlace()                  => ResponseException::makeExceptionByCode($this->translator, ErrorCodeHelper::PLAYER_WRONG_TURN),
            $tableUser->getStatus()?->value !== TableUserStatus::Active->value => ResponseException::makeExceptionByCode($this->translator, ErrorCodeHelper::USER_INACTIVE),
            default                                                            => true
        };
    }

    /**
     * Устанавливаем диллера.
     */
    public function setDealer(Table $table, array $activePlayersSortedByPlace): Table
    {
        /** @var TableUser $tableUser */
        // List of places occupied at the table.
        $places = array_keys($activePlayersSortedByPlace);
        // Nuмber of the first occupied place.
        $firstPlace = array_key_first($activePlayersSortedByPlace);
        // Hier we find out the index of the dealer's place.
        $dealerPlaceIndex = (int) array_search($table->getDealerPlace(), $places);
        // Get the index for the new dealer's place.
        $newDealerIndex = $dealerPlaceIndex + 1;
        // Get the new dealer's place, if the index is the last, then the new dealer takes the 1st place.
        $newDealerPlace = array_key_exists($newDealerIndex, $places) ? $places[$newDealerIndex] : $firstPlace;

        return $table->setDealerPlace($newDealerPlace);
    }

    /**
     * Определяем какое место является первым в очереди на ход.
     * По правилам первым должен ходить игрок после диллера.
     * Исключения:
     * 1. На префлопе первое слово за игроком после большого блайнда.
     * 2. Когда за столом 2 игрока первый ход за диллером.
     */
    public function setFirstTurn(Table $table, array $activeTableUsersSortedByPlace): Table
    {
        return $table->getRound()->value === Round::PreFlop->value
            ? $this->setFirstTurnFirstRound($table, $activeTableUsersSortedByPlace)
            : $this->setFirstTurnDefault($table, $activeTableUsersSortedByPlace);
    }

    // Move the right to act to the next active seat.
    public function changeTurnPlace(Table $table): void
    {
        $activePlayersSortedByPlace = $this->tableUserRepository->getPlayersSortedByPlace($table);
        $activePlayersSortedByPlace = $this->playerService->excludeSilentPlayers($activePlayersSortedByPlace);
        $table                      = $this->updateTurnPlace($table, $activePlayersSortedByPlace, $table->getTurnPlace());

        $this->entityManager->persist($table);
        $this->entityManager->flush();
    }

    // Checked when a player makes a raise.
    public function changeLastWordPlace(Table $table, TableUser $currentActiveTableUser): Table
    {
        $activePlayersSortedByPlace = $this->tableUserRepository->getPlayersSortedByPlace($table);

        $table = $this->updateLastWordPlace($table, $activePlayersSortedByPlace);
        $this->entityManager->persist($table);
        $this->entityManager->flush();

        return $table;
    }

    /**
     * По правилам в 1 раунде начинает круг первый игрок после большого блайнда
     */
    protected function setFirstTurnFirstRound(Table $table, array $activeTableUsersSortedByPlace): Table
    {
        // Get a list of all active seats.
        $activePlaces = array_keys($activeTableUsersSortedByPlace);
        // Get the number of the first active seat.
        $firstPlaceNumber = array_key_first($activeTableUsersSortedByPlace);
        // Get the index of the big blind place in the list of active seats.
        $lastWordPlaceIndex = array_search($table->getBigBlindPlace(), $activePlaces);
        // Get the index for the first turn.
        $firstTurnIndex = $lastWordPlaceIndex + 1;
        // Get a flag indicating that the big blind is not the last place.
        $isBigBlindNotLastPlace = $lastWordPlaceIndex !== false && array_key_exists($firstTurnIndex, $activePlaces);

        if (
            count($this->playerService->excludeSilentPlayers($activeTableUsersSortedByPlace)) > 0
            && $activeTableUsersSortedByPlace[$activePlaces[$lastWordPlaceIndex]]->getBetType()?->value === BetType::AllIn->value
        ) {
            $lastWordPlaceIndex = $this->calculateLastWordIndex($activeTableUsersSortedByPlace, $lastWordPlaceIndex);
        }

        // If the big blind is not in the last place, set the first turn to the next place after it
        if ($isBigBlindNotLastPlace) {
            $turnPlace = $activePlaces[$firstTurnIndex];
            if ($activeTableUsersSortedByPlace[$turnPlace]->getSeatOut()) {
                $betExpirationTime = time() + 10;
            } else {
                $betExpirationTime = time() + ((int)$table->getSetting()->getTurnTime() ?? static::BET_EXPIRATION_TIME);
            }
            $activeTableUsersSortedByPlace[$turnPlace]->setBetExpirationTime($betExpirationTime);

            $this->entityManager->persist($activeTableUsersSortedByPlace[$turnPlace]);
            $this->entityManager->flush();

            return $table->setTurnPlace($activePlaces[$firstTurnIndex])
                ->setLastWordPlace($activePlaces[$lastWordPlaceIndex]);
        }

        if ($activeTableUsersSortedByPlace[$firstPlaceNumber]->getSeatOut()) {
            $betExpirationTime = time() + 10;
        } else {
            $betExpirationTime = time() + ((int)$table->getSetting()->getTurnTime() ?? static::BET_EXPIRATION_TIME);
        }

        // If the big blind is in the last place, set the first turn to the first place in the list.
        $activeTableUsersSortedByPlace[$firstPlaceNumber]
            ->setBetExpirationTime($betExpirationTime);
        $table->setTurnPlace($firstPlaceNumber)
            ->setLastWordPlace($activePlaces[$lastWordPlaceIndex]);

        $this->entityManager->persist($activeTableUsersSortedByPlace[$firstPlaceNumber]);
        $this->entityManager->flush();

        return $table;
    }

    /**
     * По правилам первый ход имее игрок первый после диллера.
     * Так как игроки могут прибывать в состоянии неактивности в том числе и диллер,
     * нам нужно взять весь список игроков за столом и найти первое активное после диллера
     */
    protected function setFirstTurnDefault(Table $table, array $activeTableUsersSortedByPlace): Table
    {
        // Get a list of all seats at the table sorted by place.
        $activeTableUsersSortedByPlace = $this->playerService->excludeSilentPlayers($activeTableUsersSortedByPlace);
        $table                         = $this->updateTurnPlace($table, $activeTableUsersSortedByPlace, $table->getDealerPlace());

        // Update the right to the last word.
        return $this->updateLastWordPlace($table, $activeTableUsersSortedByPlace);
    }

    /**
     * Двигаем право на ход, следуюшему активному игроку.
     */
    protected function updateTurnPlace(
        Table $table,
        array $activeTableUsersSortedByPlace,
        int $currentPlace
    ): Table {
        // Get a list of all players at the table sorted by ascending places.
        $sortedAllTableUsers = $this->sortTableUsersByPlaceAsc($table->getTableUsers()->toArray());
        // Get a list of all places.
        $places = array_keys($sortedAllTableUsers);
        // Get the index of the place next to the current one.
        $nextTurnPlaceIndex = (int) array_search($currentPlace, $places) + 1;
        // Get the number of places in the list after the current one.
        $countPlaceAfterCurrentTurn = count($sortedAllTableUsers) - $nextTurnPlaceIndex;

        // If the current seat is not the last seat at the table, then we cut out all seats after the current one and add them to the beginning of the list.
        // We do this to make it easier to find the next active seat.
        if ($countPlaceAfterCurrentTurn > 0) {
            $tableUsersAfterCurrentTurn          = array_slice($sortedAllTableUsers, -$countPlaceAfterCurrentTurn);
            $tableUsersBeforeCurrentTurnIncluded = array_slice($sortedAllTableUsers, 0, $nextTurnPlaceIndex);
            // Sort users from dealer
            $sortedAllTableUsers = array_merge($tableUsersAfterCurrentTurn, $tableUsersBeforeCurrentTurnIncluded);
        }

        // Get the next active player after the current one and assign them the right to act.
        /** @var TableUser $tableUser */
        foreach ($sortedAllTableUsers as $tableUser) {
            if (array_key_exists($tableUser->getPlace(), $activeTableUsersSortedByPlace)) {
                if ($tableUser->getSeatOut()) {
                    $tableUser->setBetExpirationTime(time() + 10);
                } else {
                    $tableUser->setBetExpirationTime(time() + ((int)$table->getSetting()->getTurnTime() ?? static::BET_EXPIRATION_TIME));
                }
                $table->setTurnPlace($tableUser->getPlace());

                $this->entityManager->persist($tableUser);
                break;
            }
        }

        $this->entityManager->flush();

        return $table;
    }

    /**
     * В начале игры право последнего хода, получает игрок который сидит перед игроком у которого право первого хода.
     *
     * Когда игрок походил и сделал повышающую ставку,
     * мы передаем право последнего хода, игроку который находится перед ним.
     *
     * В других случаях право последнего хода не меняется.
     */
    protected function updateLastWordPlace(Table $table, array $activeTableUsersSortedByPlace): Table
    {
        $lastWordPlace = 0;
        foreach ($activeTableUsersSortedByPlace as $activeTableUserSortedByPlace) {
            if ($activeTableUserSortedByPlace->getPlace() === $table->getTurnPlace()) {
                break;
            }

            if (
                $activeTableUserSortedByPlace->getBetType()
                && $activeTableUserSortedByPlace->getBetType()?->value !== BetType::AllIn->value
                && $activeTableUserSortedByPlace->getBetType()?->value !== BetType::Fold->value
            ) {
                $lastWordPlace = $activeTableUserSortedByPlace->getPlace();
            }
        }

        $activeTableUsersSortedByPlace = $this->playerService->excludeSilentPlayers($activeTableUsersSortedByPlace);
        if (!$lastWordPlace) {
            $lastWordPlace = array_key_last($activeTableUsersSortedByPlace);
        }
        $table->setLastWordPlace($lastWordPlace);

        return $table;
    }

    protected function sortTableUsersByPlaceAsc(array $tableUsers): array
    {
        usort(
            $tableUsers,
            fn($prev, $next) => $prev->getPlace() <=> $next->getPlace()
        );

        $sortedTableUsers = [];
        foreach ($tableUsers as $tableUser) {
            $sortedTableUsers[$tableUser->getPlace()] = $tableUser;
        }

        return $sortedTableUsers;
    }

    private function calculateLastWordIndex(array $activeTableUsersSortedByPlace, int $currentPlaceIndex): int
    {
        $activePlaces = array_keys($activeTableUsersSortedByPlace);

        // Use do-while to ensure the loop runs at least once
        do {
            // Funded the index of the previous seat at the table
            $currentPlaceIndex = $currentPlaceIndex === 0 ? count($activePlaces) - 1 : $currentPlaceIndex - 1;

            // Get the place
            $currentPlace = $activePlaces[$currentPlaceIndex];

            // Get the bet type of the current player
            $currentBetType = $activeTableUsersSortedByPlace[$currentPlace]->getBetType()?->value;
        } while ($currentBetType === BetType::AllIn->value);

        return $currentPlaceIndex;
    }
}
