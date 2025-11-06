<?php

declare(strict_types=1);

namespace App\Handler\Balance;

use App\Exception\ResponseException;
use App\Helper\ErrorCodeHelper;
use App\Entity\{Tournament, User};
use App\Handler\AbstractHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class BuyInTournamentHandler extends AbstractHandler
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

        // check if the user has enough balance
        if (bccomp($actualBalance, $tournamentEntrySumString, 2) === -1) {
            ResponseException::makeExceptionByCode($this->translator, ErrorCodeHelper::INCORRECT_BALANCE);
        }

        // subtract from the balance
        $newBalanceString = bcsub($actualBalance, $tournamentEntrySumString, 2);

        $this->entityManager->getConnection()->beginTransaction();

        try {
            $rakeAmount = $tournament->getSetting()->getEntrySum() * $tournament->getSetting()->getRake();
            $tournament->setBalance($tournament->getBalance() + $tournament->getSetting()->getEntrySum() - $rakeAmount);
            $user->setBalance($newBalanceString);

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
