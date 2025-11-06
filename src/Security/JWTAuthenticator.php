<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator as BaseJWTAuthenticator;
use Symfony\Component\HttpFoundation\Request;

class JWTAuthenticator extends BaseJWTAuthenticator
{
    public function supports(Request $request): bool
    {
        dd('JWTAuthenticator::supports', $request->headers->all());
        // Тепер JWT працює на ВСІХ методах (GET, POST, PUT, DELETE — будь-якому)
        return $request->headers->has('Authorization');
    }
}