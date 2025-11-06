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

class ApproveCacheTablePlayerInvoicesHandler extends AbstractHandler
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

    public function __invoke(TableUser $player)
    {
        $pendingInvoices = $this->tableUserInvoiceRepository->findBy([
            'table'  => $player->getTable(),
            'user'   => $player->getUser(),
            'status' => TableUserInvoiceStatus::Pending
        ]);

        $user = $player->getUser();

        if (!$pendingInvoices) {
            return;
        }

        $this->entityManager->getConnection()->beginTransaction();
        $handledInvoices = [];

        try {
            /** @var TableUserInvoice $invoice */
            foreach ($pendingInvoices as $invoice) {
                $chips        = Calculator::add($invoice->getSum(), $player->getStack());
                $invoiceSumString = (string) $invoice->getSum();
                $actualBalance = $user->getBalance();

                if (bccomp($actualBalance, $invoiceSumString, 2) === 1) {

                    // Deduct the invoice sum from the user's balance
                    $newBalance = bcsub($actualBalance, $invoiceSumString, 2);
                    $user->setBalance($newBalance);
                    
                    $player->setStack($chips);
                    $invoice->setStatus(TableUserInvoiceStatus::Completed);

                    $this->entityManager->persist($invoice);
                    $this->entityManager->persist($player);
                    $this->entityManager->persist($user);

                    continue;
                }

                $invoice->setStatus(TableUserInvoiceStatus::Failed);
                $this->entityManager->persist($invoice);

                // Keep track of handled invoices for potential rollback
                $handledInvoices[] = $invoice;
            }

            $player->setStatus(TableUserStatus::Active);
            $this->entityManager->persist($player);
            $this->entityManager->flush();

            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            // We roll back the transaction in case of an error
            $this->entityManager->getConnection()->rollBack();

            foreach ($handledInvoices as $invoice) {
                // Revert the invoice
                $invoiceString = (string) $invoice->getSum();
                $actualBalance = $user->getBalance();
                $newActualBalance = bcadd($actualBalance, $invoiceString, 2);
                $user->setBalance($newActualBalance);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            throw $e;
        }
    }
}
