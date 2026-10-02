<?php

declare(strict_types=1);

namespace App\Response;

use App\Entity\TableHistory;
use App\Helper\Calculator;
use App\ValueObject\TableHistory\RoundActionTableHistory;
use App\ValueObject\TableHistory\WinnerTableHistory;

class TableHistoryResponse
{
    public static function list(TableHistory $tableHistory, ?string $currentLogin): array
    {
        $players = $tableHistory->getPlayers();
        $cards = [];

        foreach ($players as $player) {
            if ($player->getLogin() === $currentLogin) {
                $cards = $player->getCards();
                break;
            }
        }

        $winners = $tableHistory->getWinners();
        $bank = array_reduce(
            $winners,
            static fn(float $total, WinnerTableHistory $winner): float => Calculator::add($total, $winner->getSum()),
            0.0
        );

        $myResult = null;
        if ($currentLogin !== null) {
            foreach ($tableHistory->getResults() as $result) {
                if ($result->getPlayer() === $currentLogin) {
                    $myResult = [
                        'deltaChips' => $result->getDeltaChips(),
                        'finalStack' => $result->getFinalStack(),
                    ];
                    break;
                }
            }
        }

        return [
            'session' => $tableHistory->getSession(),
            'handNumber' => $tableHistory->getHandNumber(),
            'status' => $tableHistory->getStatus(),
            'startedAt' => (new \DateTimeImmutable())->setTimestamp($tableHistory->getStartedAt())->format(DATE_ATOM),
            'endedAt' => $tableHistory->getEndedAt() !== null
                ? (new \DateTimeImmutable())->setTimestamp($tableHistory->getEndedAt())->format(DATE_ATOM)
                : null,
            'cards' => $cards,
            'winners' => array_map(
                static fn(WinnerTableHistory $winner): string => $winner->getLogin(),
                $winners
            ),
            'bank' => $bank,
            'myResult' => $myResult,
        ];
    }

    public static function item(TableHistory $tableHistory, ?array $players = null): array
    {
        return [
            'session'    => $tableHistory->getSession(),
            'handNumber' => $tableHistory->getHandNumber(),
            'gameType'   => $tableHistory->getGameType(),
            'status'     => $tableHistory->getStatus(),
            'players'    => $players ?? array_values($tableHistory->getPlayers()),
            'blinds'     => $tableHistory->getBlinds()->toArray(),
            'dealer'     => $tableHistory->getDealer(),
            'boardCards' => $tableHistory->getCards(true),
            'preflop'    => array_map(fn(RoundActionTableHistory $roundAction) => $roundAction->toArray(), $tableHistory->getPreflop()),
            'flop'       => array_map(fn(RoundActionTableHistory $roundAction) => $roundAction->toArray(), $tableHistory->getFlop()),
            'turn'       => array_map(fn(RoundActionTableHistory $roundAction) => $roundAction->toArray(), $tableHistory->getTurn()),
            'river'      => array_map(fn(RoundActionTableHistory $roundAction) => $roundAction->toArray(), $tableHistory->getRiver()),
            'pot'        => $tableHistory->getPot()->toArray(),
            'winners'    => array_map(fn(WinnerTableHistory $winner) => $winner->toArray(), $tableHistory->getWinners()),
            'results'    => array_map(fn($result) => [
                'player' => $result->getPlayer(),
                'seat' => $result->getSeat(),
                'deltaChips' => $result->getDeltaChips(),
                'finalStack' => $result->getFinalStack(),
            ], $tableHistory->getResults()->toArray()),
        ];
    }
}
