<?php

declare(strict_types=1);

namespace App\Handler\Balance;

use App\Entity\TableUser;
use App\Enum\TableUserInvoiceStatus;
use App\Handler\AbstractHandler;
use App\Repository\TableUserInvoiceRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReturnRemainingsPlayerBalanceHandler extends AbstractHandler
{
    public function __construct(
        protected Security $security,
        protected TranslatorInterface $translator,
        protected TableUserInvoiceRepository $tableUserInvoiceRepository,
        protected UserService $userService,
        protected EntityManagerInterface $entityManager
    ) {
        parent::__construct($security, $translator);
    }

    public function __invoke(TableUser $player)
    {
        if ($player->getTable()->getTournament()) {
            return;
        }

        $pendingInvoices = $this->tableUserInvoiceRepository->findBy([
            'table'  => $player->getTable(),
            'user'   => $player->getUser(),
            'status' => TableUserInvoiceStatus::Pending
        ]);

        $user = $player->getUser();

        $stackString = (string) $player->getStack();
        $actualBalance = $user->setBalance(bcadd($user->getBalance(), $stackString, 2));
        $this->entityManager->persist($user);

        $player->setStack(0);

        $this->entityManager->getConnection()->beginTransaction();

        try {
            /** @var TableUserInvoice $invoice */
            foreach ($pendingInvoices as $invoice) {
                // Return money to user balance
                $invoice->setStatus(TableUserInvoiceStatus::Back);
                $this->entityManager->persist($invoice);
            }

            $this->entityManager->remove($player);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            // We roll back the transaction in case of an error
            $this->entityManager->getConnection()->rollBack();
            $user->setBalance(bcsub((string) $actualBalance, $stackString, 2));
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            throw $e;
        }
    }
}
