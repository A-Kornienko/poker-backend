<?php

namespace App\Handler\Cards\Combination\OmahaHigh;

use App\Enum\CardCombinationRank;
use App\Enum\CardType;
use App\Handler\Cards\Combination\Base\AbstractBaseCombinationHandler;
use App\ValueObject\Card;
use App\ValueObject\Combination;

class HighCardCombinationHandler extends AbstractBaseCombinationHandler implements CombinationHandlerInterface
{
    public function getCombination(Card ...$cards): ?Combination
    {
        $cards = $this->sortCardDesc($cards);

        // Separate cards into hand and table types.
        $handKickers  = array_filter($cards, fn($card) => $card->getType() === CardType::Hand);
        $tableKickers = array_filter($cards, fn($card) => $card->getType() === CardType::Table);

        // Union of the first 2 hand cards and the first 3 table cards.
        $combination = $this->sortCardDesc(array_merge(
            array_slice($handKickers, 0, 2),
            array_slice($tableKickers, 0, 3)
        ));

        // Return a combination of the 5 best cards.
        return (new Combination())
            ->setName(CardCombinationRank::HighCard->name)
            ->setRank(CardCombinationRank::HighCard->value)
            ->setCards($combination);
    }

    public static function getDefaultPriority(): int
    {
        return CardCombinationRank::HighCard->value;
    }
}
