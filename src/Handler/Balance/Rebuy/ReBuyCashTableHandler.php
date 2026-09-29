<?php

declare(strict_types=1);

namespace App\Handler\Balance\Rebuy;

use App\Entity\{Table, User};

class ReBuyCashTableHandler extends AbstractRebuyBalanceHandler
{
    public function __invoke(User $user, Table $table, float $stack): void
    {
        $this->defaultBuyIn($table,$user, $stack);
    }
}
