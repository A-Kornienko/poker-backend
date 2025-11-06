<?php

declare(strict_types=1);

namespace App\Handler\Balance;

use App\Entity\TableUser;
use App\Enum\{TableUserInvoiceStatus, TableUserStatus};
use App\Handler\AbstractHandler;
use App\Helper\Calculator;
use App\Repository\TableUserInvoiceRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApproveTournamentPlayerInvoicesHandler extends AbstractHandler
{
    public function __construct(
        protected Security $security,
        protected TranslatorInterface $translator,
        protected TableUserInvoiceRepository $tableUserInvoiceRepository,
        protected UserService $userService,
        protected EntityManagerInterface $entityManager,
        protected EventDispatcherInterface $dispatcher
    ) {
        parent::__construct($security, $translator);
    }

    public function __invoke(TableUser $player): void
    {
        $pendingInvoices = $this->tableUserInvoiceRepository->findBy([
            'table'  => $player->getTable(),
            'user'   => $player->getUser(),
            'status' => TableUserInvoiceStatus::Pending
        ]);

        $user                = $player->getUser();
        $tournamentSum       = $player->getTable()->getTournament()->getSetting()->getBuyInSettings()->getSum();
        $tournamentSumString = (string) $tournamentSum;

        if (!$pendingInvoices) {
            return;
        }

        $this->entityManager->getConnection()->beginTransaction();
        $handledInvoices = [];

        try {
            /** @var TableUserInvoice $invoice */
            foreach ($pendingInvoices as $invoice) {
                $chips        = Calculator::add($invoice->getSum(), $player->getStack());
                $actualBalance = $user->getBalance();

                if (bccomp($actualBalance, $tournamentSumString, 2) === 1) {
                    // We subtract the tournament amount from the user's balance
                    $newBalance = bcsub($actualBalance, $tournamentSumString, 2);
                    $user->setBalance($newBalance);
                    $this->entityManager->persist($user);

                    // We add the chips to the player's stack
                    $player->setStack($chips);
                    $player->setCountByuIn($player->getCountByuIn() + 1);
                    $invoice->setStatus(TableUserInvoiceStatus::Completed);

                    $this->entityManager->persist($invoice);
                    continue;
                }

                $invoice->setStatus(TableUserInvoiceStatus::Failed);
                $this->entityManager->persist($invoice);

                $handledInvoices[] = $invoice;
            }

            $player->setStatus(TableUserStatus::Active);
            $this->entityManager->persist($player);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            // Something went wrong, we need to rollback the transaction
            $this->entityManager->getConnection()->rollBack();

            foreach ($handledInvoices as $invoice) {
                $InvoiceSumString = (string) $invoice->getSum();
                $actualBalance    = $user->getBalance();
                $newBalance       = bcadd($actualBalance, $InvoiceSumString, 2);
                $user->setBalance($newBalance);
                $this->entityManager->persist($user);
            }

            throw $e;
        }
    }
}
