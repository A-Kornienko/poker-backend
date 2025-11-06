<?php

declare(strict_types=1);

namespace App\Handler\Cards\Combination;

class CompareCombinationsHandler
{
    public function __invoke(array $combinationCardsA, array $combinationCardsB): int
    {
        for ($i = 0; $i < 5; $i++) {
            if ($combinationCardsA[$i]->getValue() !== $combinationCardsB[$i]->getValue()) {
                // If the values are not equal, return the difference between them
                return $combinationCardsB[$i]->getValue() - $combinationCardsA[$i]->getValue();
            }
        }

        // If all values are equal, return 0
        return 0;
    }
}
