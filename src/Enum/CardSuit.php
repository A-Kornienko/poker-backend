<?php

declare(strict_types=1);

namespace App\Enum;

enum CardSuit: string
{
    case Diamond = 'Diamond'; // Бубни
    case Club    = 'Club'; // Трефи
    case Heart   = 'Heart'; // Черви
    case Spade   = 'Spade'; // Пики
}
