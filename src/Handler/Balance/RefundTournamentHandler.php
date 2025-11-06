<?php

declare(strict_types=1);

namespace App\Handler\Balance;

use App\Entity\{Tournament, User};
use App\Handler\AbstractHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class RefundTournamentHandler extends AbstractHandler
{
    public function __construct(
        protected Security $security,
        protected TranslatorInterface $translator,
        protected EntityManagerInterface $entityManager
    ) {
        parent::__construct($security, $translator);
    }

    public function __invoke(Tournament $tournament, User $user): void
    {
        $tournamentEntrySumString = (string) $tournament->getSetting()->getEntrySum();
        $actualBalance = $user->getBalance();
        $actualTournamentBalance = $tournament->getBalance();
        // add to the balance
        $newActualBalance = bcadd($actualBalance, $tournamentEntrySumString, 2);

        $this->entityManager->getConnection()->beginTransaction();

        try {
            $rakeAmount = $tournament->getSetting()->getEntrySum() * $tournament->getSetting()->getRake();
            $tournament->setBalance($tournament->getBalance() - ($tournament->getSetting()->getEntrySum() - $rakeAmount));
            $user->setBalance($newActualBalance);

            $this->entityManager->persist($tournament);
            $this->entityManager->persist($user);
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            // We roll back the transaction in case of an error
            $this->entityManager->getConnection()->rollBack();
            $user->setBalance($actualBalance);
            $tournament->setBalance($actualTournamentBalance);
            $this->entityManager->persist($user);
            $this->entityManager->persist($tournament);

            throw $e;
        }
    }
}
