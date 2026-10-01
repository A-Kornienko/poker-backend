<?php

declare(strict_types=1);

namespace App\Response;

use App\Entity\PlayerSetting;
use App\Enum\StackViewCurrency;
use App\ValueObject\ButtonMacros;

class PlayerSettingResponse
{
    public static function item(PlayerSetting $playerSetting): array
    {
        return [
            'stackView'                => $playerSetting->getStackView()->toArray(),
            'stackViewCurrencyOptions' => StackViewCurrency::toArray(),
            'cardSqueeze'              => $playerSetting->getCardSqueeze(),
            'buttonMacros'             => $playerSetting->getButtonMacros()->toArray(),
            'buttonMacrosOptions'      => (new ButtonMacros())->toArray(),
        ];
    }
}
