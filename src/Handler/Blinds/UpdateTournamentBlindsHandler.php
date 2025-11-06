<?php

declare(strict_types=1);

namespace App\Handler\Blinds;

use App\Entity\Table;
use App\Enum\TournamentStatus;
use Doctrine\ORM\EntityManagerInterface;

class UpdateTournamentBlindsHandler
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(Table $table): void
    {
        if (!$table->getTournament()) {
            return;
        }

        $tournament = $table->getTournament();
        // If the tournament status is Break, update the last update time and save changes
        if ($tournament->getStatus() === TournamentStatus::Break) {
            // check if blinds were already updated during this break
            $breakEndTime = $tournament->getLastBlindUpdate() + $tournament->getSetting()->getBreakSettings()->getDuration();
            if (time() < $breakEndTime) {
                return; // If the current time is less than the break end time, do nothing
                
            }

            // Update the last update time considering the break
            $tournament->setLastBlindUpdate($breakEndTime);
            $this->entityManager->persist($tournament);
            $this->entityManager->flush();
        }

        $increaseTimeBlinds = $tournament->getLastBlindUpdate() + $tournament->getSetting()->getBlindSetting()->getBlindSpeed();
        if ($increaseTimeBlinds <= time()) {
            // Get the coefficient
            $coefficient = $tournament->getSetting()->getBlindSetting()->getBlindCoefficient();
            $table->setSmallBlind($table->getSmallBlind() * $coefficient);
            $table->setBigBlind($table->getBigBlind() * $coefficient);
            $tournament->setLastBlindUpdate(time());
            
            $this->entityManager->persist($tournament);
            $this->entityManager->persist($table);
        }

        $this->entityManager->flush();
    }
}
