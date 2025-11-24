<?php

declare(strict_types=1);

namespace App\Response;

use App\Entity\User;

class UserResponse
{
    public static function item(User $user): array
    {
        return [
            'user' => [
                // 'id'       => $user->getId(),
                'login'    => $user->getLogin(),
                'email'    => $user->getEmail(),
            ],
        ];
    }
}