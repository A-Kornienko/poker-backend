<?php

declare(strict_types=1);

namespace App\Handler\Balance;

use App\Entity\TableUser;
use App\Exception\ResponseException;
use App\Handler\AbstractHandler;
use App\Helper\ErrorCodeHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class BuyInCashTableHandler extends AbstractHandler
{
    public function __construct(
        protected Security $security,
        protected TranslatorInterface $translator,
        protected EntityManagerInterface $entityManager,
    ) {
        parent::__construct($security, $translator);
    }

    public function __invoke(TableUser $player, float $chips): void
    {
        $user = $player->getUser();

        $actualBalance = $user->getBalance();
        $chipsString = (string) $chips;

        if (bccomp($actualBalance, $chipsString, 2) === -1) {
            ResponseException::makeExceptionByCode($this->translator, ErrorCodeHelper::INCORRECT_BALANCE);
        }
        // subtract from the balance
        $newActualBalance = bcsub($actualBalance, $chipsString, 2);

        $this->entityManager->getConnection()->beginTransaction();

        try {
            $player->setStack($chips);
            $user->setBalance($newActualBalance);
            $this->entityManager->persist($player);
            $this->entityManager->persist($user);

            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            // We roll back the transaction in case of an error
            $this->entityManager->getConnection()->rollBack();
            $user->setBalance($actualBalance);
            $this->entityManager->persist($user);
            
            throw $e;
        }
    }
}
