<?php

declare(strict_types=1);

namespace App\Handler\TableHistory\Add;

use App\Entity\TableHistory;
use App\Entity\TableHistoryAction;
use App\Enum\Round;
use App\Event\TableHistory\PlayerActionEvent;
use App\Repository\TableHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PlayerActionTableHistoryHandler implements AddTableHistoryHandlerInterface
{
    public function __construct(
        protected TableHistoryRepository $tableHistoryRepository,
        protected EntityManagerInterface $entityManager
    ) {
    }

    public function getRelatedEvent(): string
    {
        return PlayerActionEvent::NAME;
    }

    public function __invoke(Event $event): void
    {
        /** @var PlayerActionEvent $event */
        /** @var TableHistory $tableHistory */
        $tableHistory = $this->tableHistoryRepository->findOneBy(['session' => $event->getSession()]);

        if (!$tableHistory) {
            return;
        }

        $tableHistory->addAction((new TableHistoryAction())
            ->setTableHistory($tableHistory)
            ->setPlayer($event->getLogin())
            ->setSeat((int) $event->getPlace())
            ->setRound($event->getRound())
            ->setActionType($event->getActionType())
            ->setBetType($event->getBetType())
            ->setAmount($event->getAmount())
            ->setSequenceNumber($tableHistory->getActions()->count() + 1));

        $this->entityManager->persist($tableHistory);
        $this->entityManager->flush();
    }
}
