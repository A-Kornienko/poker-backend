<?php

declare(strict_types=1);

namespace App\Handler\Balance\Rebuy;

use App\Entity\Table;
use App\Entity\TableUser;
use App\Entity\User;
use App\Enum\TableUserInvoiceStatus;
use App\Enum\TableUserStatus;
use App\Exception\ResponseException;
use App\Helper\Calculator;
use App\Helper\ErrorCodeHelper;
use App\Repository\TableUserInvoiceRepository;
use App\Repository\TableUserRepository;
use App\Service\TableUserInvoiceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractRebuyBalanceHandler
{
    public function __construct(
        protected Security $security,
        protected TranslatorInterface $translator,
        protected EntityManagerInterface $entityManager,
        protected TableUserInvoiceRepository $tableUserInvoiceRepository,
        protected TableUserInvoiceService $tableUserInvoiceService,
        protected TableUserRepository $tableUserRepository,
    ) {}

    protected function defaultLoseValidation(TableUser $player, float $amount): void
    {
        match (true) {
            $player->getStack() > 0
            => ResponseException::makeExceptionByCode($this->translator, ErrorCodeHelper::BIG_BALANCE_FOR_BUY_IN),
            $amount < $player->getTable()->getSetting()->getBigBlind() * 20 && $amount > $player->getTable()->getSetting()->getBigBlind() * 500
            => ResponseException::makeExceptionByCode($this->translator, ErrorCodeHelper::INCORRECT_BALANCE),
            $player->getStatus() !== TableUserStatus::Lose => ErrorCodeHelper::YOU_ARE_NOT_A_LOSER,
            default => true
        };
    }

    protected function validateTournamentBuyIn(TableUser $player, float $amount): void
    {
        $tournament = $player->getTable()->getTournament();

        match (true) {
            $tournament->getSetting()->getBuyInSettings()->getSum() < $amount
            => ResponseException::makeExceptionByCode($this->translator, ErrorCodeHelper::TOURNAMENT_AMOUNT_BUY_IN_BIG),
            $tournament->getSetting()->getBuyInSettings()->getSum() > $amount
            => ResponseException::makeExceptionByCode($this->translator, ErrorCodeHelper::TOURNAMENT_AMOUNT_BUY_IN_SMALL),
            $tournament->getDateStart() + $tournament->getSetting()->getBuyInSettings()->getLimitByTime() < time()
            => ResponseException::makeExceptionByCode($this->translator, ErrorCodeHelper::TOURNAMENT_BUY_IN_LIMIT_TIME),
            $tournament->getTournamentUsers()->count() <= $tournament->getSetting()->getBuyInSettings()->getLimitByCountPlayers()
            => ResponseException::makeExceptionByCode($this->translator, ErrorCodeHelper::TOURNAMENT_BUY_IN_LIMIT_COUNT_PLAYERS),
            $tournament->getSetting()->getEntryChips() * $tournament->getSetting()->getBuyInSettings()->getLimitByChipsInPercent() / 100 < $player->getStack()
            => ResponseException::makeExceptionByCode($this->translator, ErrorCodeHelper::TOURNAMENT_BUY_IN_TOO_MANY_CHIPS),
            $tournament->getSetting()->getBuyInSettings()->getLimitByNumberOfTimes() <= $player->getCountByuIn()
            => ResponseException::makeExceptionByCode($this->translator, ErrorCodeHelper::TOURNAMENT_BUY_IN_LIMIT_BY_NUMBER_TIMES),

            default => true
        };
    }

    protected function getTableUser(User $user, Table $table): TableUser
    {
        $player = $this->tableUserRepository->findOneBy([
            'user'  => $user,
            'table' => $table
        ]);

        if (!$player) {
            ResponseException::makeExceptionByCode($this->translator, ErrorCodeHelper::PLAYER_NOT_FOUND);
        }

        return $player;
    }

    protected function defaultBuyIn(Table $table, User $user, float $stack)
    {
        $player = $this->tableUserRepository->findOneBy(['table' => $table, 'user' => $user]);

        if (!$player) {
            ResponseException::makeExceptionByCode($this->translator, ErrorCodeHelper::PLAYER_NOT_FOUND);
        }

        $pendingInvoices = $this->tableUserInvoiceRepository->findBy([
            'table'  => $player->getTable(),
            'user'   => $player->getUser(),
            'status' => TableUserInvoiceStatus::Pending
        ]);

        $sumPendingInvoices = 0;
        foreach ($pendingInvoices as $pendingInvoice) {
            $sumPendingInvoices = Calculator::add($sumPendingInvoices, $pendingInvoice->getSum());
        }

        $actualBalance = $user->getBalance();
        $sumPendingInvoicesAndStackString = (string) Calculator::add($sumPendingInvoices, $stack);
        if (bccomp($actualBalance, $sumPendingInvoicesAndStackString, 2) === -1) {
            ResponseException::makeExceptionByCode($this->translator, ErrorCodeHelper::INCORRECT_BALANCE);
        }

        $this->entityManager->getConnection()->beginTransaction();

        try {
            $this->tableUserInvoiceService->create($player, $stack);
            $this->entityManager->flush(); // We save the changes in the database
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            // We roll back the transaction in case of an error
            $this->entityManager->getConnection()->rollBack();

            throw $e;
        }
    }

    protected function addStack(TableUser $player, User $user, float $amount): void
    {
        $chips = $player->getStack();
        $amountString = (string)$amount;
        $actualBalance = $user->getBalance();

        // subtract from the balance only if there are enough funds
        if (bccomp($actualBalance, $amountString, 2) === 1) {
            $user->setBalance(
                bcsub($user->getBalance(), $amountString, 2)
            );

            $player->setStack($chips + $amount);
            $player->setStatus(TableUserStatus::Pending);

            $this->entityManager->persist($player);
            $this->entityManager->persist($user);
        }
    }
}
