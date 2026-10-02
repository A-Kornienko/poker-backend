<?php

declare(strict_types=1);

namespace App\Handler\TableHistory\Add;

use App\Entity\TableHistory;
use App\Entity\TableHistoryAction;
use App\Entity\TableHistoryPlayer;
use App\Enum\BetType;
use App\Enum\Round;
use App\Enum\RoundActionType;
use App\Event\TableHistory\StartGameEvent;
use App\Helper\Calculator;
use App\Repository\TableHistoryRepository;
use App\ValueObject\TableHistory\BlindsTableHistory;
use App\ValueObject\TableHistory\PotTableHistory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\Event;

class StartGameTableHistoryHandler implements AddTableHistoryHandlerInterface
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected TableHistoryRepository $tableHistoryRepository,
    ) {
    }

    public function getRelatedEvent(): string
    {
        return StartGameEvent::NAME;
    }

    public function __invoke(Event $event): void
    {
        /** @var StartGameEvent $event */
        $table = $event->getTable();

        $tableHistory = (new TableHistory())
            ->setTable($table)
            ->setSession($table->getSession())
            ->setHandNumber($this->tableHistoryRepository->getNextHandNumber($table))
            ->setGameType('cash')
            ->setStatus('started')
            ->setDealerSeat((int) $table->getDealerPlace())
            ->setStartedAt(time())
            ->setBlinds((new BlindsTableHistory())->fromArray([
                'smallBlindPlace' => $table->getSmallBlindPlace(),
                'bigBlindPlace' => $table->getBigBlindPlace(),
                'smallBlind'    => $table->getSmallBlind(),
                'bigBlind'      => $table->getBigBlind(),
            ]));

        foreach ($table->getTableUsers() as $player) {
            if (!$player->getCards()) {
                continue;
            }

            $tableHistory->addPlayer((new TableHistoryPlayer())
                ->setTableHistory($tableHistory)
                ->setPlace((int) $player->getPlace())
                ->setSeat((int) $player->getPlace())
                ->setLogin($player->getUser()->getLogin())
                ->setStackBefore(Calculator::add($player->getStack(), $player->getBet()))
                ->setStackAfter(Calculator::add($player->getStack(), $player->getBet()))
                ->setNetChange(0.0)
                ->setIsActive(true)
                ->setIsFolded(false)
                ->setIsAllIn($player->getBetType() === BetType::AllIn));
        }

        foreach ($table->getTableUsers() as $player) {
            $betType = match ((int) $player->getPlace()) {
                $table->getSmallBlindPlace() => BetType::SmallBlind,
                $table->getBigBlindPlace() => BetType::BigBlind,
                default => null,
            };

            if ($betType === null || $player->getBet() <= 0) {
                continue;
            }

            $tableHistory->addAction((new TableHistoryAction())
                ->setTableHistory($tableHistory)
                ->setPlayer($player->getUser()->getLogin())
                ->setSeat((int) $player->getPlace())
                ->setRound(Round::PreFlop)
                ->setActionType(RoundActionType::Bet)
                ->setBetType($betType)
                ->setAmount($player->getBet())
                ->setSequenceNumber($tableHistory->getActions()->count() + 1));
        }

        $blindPot = 0.0;
        foreach ($table->getTableUsers() as $player) {
            if (in_array((int) $player->getPlace(), [
                $table->getSmallBlindPlace(),
                $table->getBigBlindPlace(),
            ], true)) {
                $blindPot = Calculator::add($blindPot, $player->getBet());
            }
        }

        $tableHistory->setPot((new PotTableHistory())->fromArray([
            Round::PreFlop->value => $blindPot,
        ]));

        $this->entityManager->persist($tableHistory);
        $this->entityManager->flush();
    }
}
