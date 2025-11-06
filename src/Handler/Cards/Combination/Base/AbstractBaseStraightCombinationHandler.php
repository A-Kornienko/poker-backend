<?php

namespace App\Handler\Cards\Combination\Base;

use App\Enum\Card as EnumCard;
use App\ValueObject\Card;

class AbstractBaseStraightCombinationHandler extends AbstractBaseCombinationHandler
{
    public function rollbackAceValues(Card ...$cards): void
    {
        // Rollback Ace values if straights not found
        foreach ($cards as $card) {
            if ($card->getName() === EnumCard::Ace->name && $card->getValue() === 1) {
                $card->setValue(EnumCard::Ace->value);
            }
        }
    }

    protected function getAllStraightCombinations(array $cards, int $length = 5)
    {
        // 1 Step: Group by values and collect types and suits
        $valueMap = $this->groupCardsByValue($cards);
        // Get sorted unique values
        $uniqueValues = array_keys($valueMap);
        $straights    = [];

        // 2 Step: Find all possible consecutive sequences
        for ($i = 0; $i <= count($uniqueValues) - $length; $i++) {
            $currentSequence = array_slice($uniqueValues, $i, $length);
            // Checking that the sequence is indeed consecutive
            $isConsecutive = true;
            for ($j = 0; $j < $length - 1; $j++) {
                if ($currentSequence[$j] - 1 !== $currentSequence[$j + 1]) {
                    $isConsecutive = false;
                    break;
                }
            }

            if (!$isConsecutive) {
                continue;
            }

            // 3 Step: Generate all type and suit combinations for the current sequence
            $typeSuitList = [];
            foreach ($currentSequence as $value) {
                $typeSuitList[] = $valueMap[$value];
            }

            $typeSuitCombinations = $this->cartesianCard($typeSuitList);
            foreach ($typeSuitCombinations as $typeSuitCombinationCard) {
                // Get all cards for the current straight combination
                $combination = [];
                for ($k = 0; $k < $length; $k++) {
                    $combination[] = $typeSuitCombinationCard[$k];
                }
                $straights[] = $combination;
            }
        }

        // 4 Step: Filter unique combinations
        $uniqueStraights = [];
        $seenSignatures  = [];

        foreach ($straights as $straight) {
            // create signature based on types and suits
            $signature = '';
            foreach ($straight as $item) {
                $signature .= $item->__toString();
            }
            if (!in_array($signature, $seenSignatures, true)) {
                $seenSignatures[]  = $signature;
                $uniqueStraights[] = $straight;
            }
        }

        return $uniqueStraights;
    }

    // Function to compute the Cartesian product of arrays of Card objects
    protected function cartesianCard($typeSuitList)
    {
        $result = [[]];
        foreach ($typeSuitList as $cards) {
            $tmp = [];
            foreach ($result as $resultItem) {
                foreach ($cards as $card) {
                    $tmp[] = array_merge($resultItem, [$card]);
                }
            }
            $result = $tmp;
        }

        return $result;
    }
}
