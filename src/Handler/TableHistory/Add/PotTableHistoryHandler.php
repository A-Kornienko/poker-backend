<?php

namespace App\Handler\TableHistory\Add;

use App\Entity\Bank;
use App\Entity\TableHistory;
use App\Enum\Round;
use App\Event\TableHistory\PotEvent;
use App\Helper\Calculator;
use App\Repository\TableHistoryRepository;
use App\ValueObject\TableHistory\PotTableHistory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PotTableHistoryHandler implements AddTableHistoryHandlerInterface
{
    public function __construct(
        protected TableHistoryRepository $tableHistoryRepository,
        protected EntityManagerInterface $entityManager
    ) {
    }

    public function getRelatedEvent(): string
    {
        return PotEvent::NAME;
    }

    public function __invoke(Event $event): void
    {
        /** @var PotEvent $event */
        $table = $event->getTable();
        $banks = $event->getBanks();

        $tableHistory = $this->tableHistoryRepository->findOneBy([
            'table'   => $table,
            'session' => $table->getSession()
        ]);

        if ($tableHistory === null) {
            return;
        }

        $potTableHistory = $tableHistory->getPot() ?? new PotTableHistory();
        $banksSum        = array_map(fn(Bank $bank) => $bank->getSum(), $banks);
        $pot             = array_reduce($banksSum, fn($carry, $item) => Calculator::add($carry, $item), 0);

        $potRound = match ($table->getRound()) {
            Round::Flop => Round::PreFlop,
            Round::Turn => Round::Flop,
            Round::River => Round::Turn,
            Round::ShowDown => Round::River,
            Round::FastFinish => $this->getLastActionRound($tableHistory),
            default => $table->getRound(),
        };

        match ($potRound) {
            Round::PreFlop => $potTableHistory->setPreFlop($pot),
            Round::Flop => $potTableHistory->setFlop($pot),
            Round::Turn => $potTableHistory->setTurn($pot),
            Round::River => $potTableHistory->setRiver($pot),
            default => null,
        };

        $tableHistory->setPot($potTableHistory);

        $this->entityManager->persist($tableHistory);
        $this->entityManager->flush();
    }

    private function getLastActionRound(TableHistory $tableHistory): Round
    {
        $lastAction = null;
        foreach ($tableHistory->getActions() as $action) {
            if ($lastAction === null || $action->getSequenceNumber() > $lastAction->getSequenceNumber()) {
                $lastAction = $action;
            }
        }

        return $lastAction?->getRound() ?? Round::PreFlop;
    }
}
