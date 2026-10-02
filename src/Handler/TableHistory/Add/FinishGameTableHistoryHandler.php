<?php

namespace App\Handler\TableHistory\Add;

use App\Enum\BetType;
use App\Entity\TableHistoryPlayer;
use App\Entity\TableHistoryResult;
use App\Event\TableHistory\FinishGameEvent;
use App\Enum\Round;
use App\Helper\Calculator;
use App\Repository\TableHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\Event;

class FinishGameTableHistoryHandler implements AddTableHistoryHandlerInterface
{
    public function __construct(
        protected TableHistoryRepository $tableHistoryRepository,
        protected EntityManagerInterface $entityManager
    ) {
    }

    public function getRelatedEvent(): string
    {
        return FinishGameEvent::NAME;
    }

    public function __invoke(Event $event): void
    {
        /** @var FinishGameEvent $event */
        $table        = $event->getTable();
        $tableHistory = $this->tableHistoryRepository->findOneBy([
            'table'   => $table,
            'session' => $table->getSession()
        ]);

        if (!$tableHistory) {
            return;
        }

        $tableHistory
            ->setStatus('finished')
            ->setEndedAt(time())
            ->setCards(...$table->getCards());

        foreach ($table->getTableUsers() as $player) {
            $login = $player->getUser()->getLogin();
            $seat = (int) $player->getPlace();
            $stackAfter = (float) $player->getStack();
            $historyPlayer = $tableHistory->getPlayerRecord($login, $seat);

            if ($historyPlayer === null) {
                continue;
            }

            $netChange = Calculator::subtract($stackAfter, $historyPlayer->getStackBefore());
            $historyPlayer
                ->setStackAfter($stackAfter)
                ->setNetChange($netChange)
                ->setIsFolded($player->getBetType() === BetType::Fold)
                ->setIsAllIn($player->getBetType() === BetType::AllIn);

            if ($table->getRound() === Round::ShowDown && $player->getBetType() !== BetType::Fold) {
                $historyPlayer->setCards($player->getCards(true));
            }

            $result = null;
            foreach ($tableHistory->getResults() as $existingResult) {
                if ($existingResult->getPlayer() === $login && $existingResult->getSeat() === $seat) {
                    $result = $existingResult;
                    break;
                }
            }

            if ($result === null) {
                $result = (new TableHistoryResult())
                    ->setTableHistory($tableHistory)
                    ->setPlayer($login)
                    ->setSeat($seat);
                $tableHistory->addResult($result);
            }

            $result
                ->setDeltaChips($netChange)
                ->setFinalStack($stackAfter);
        }

        $this->entityManager->persist($tableHistory);
        $this->entityManager->flush();
    }
}
