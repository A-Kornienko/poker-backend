<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use App\Entity\User;

#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_created', method: 'onJWTCreated')]
class JWTListener
{
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        /** @var User $user */
        $user = $event->getUser();
        $payload = $event->getData();
        $payload['email'] = $user->getEmail();
        $payload['login'] = $user->getLogin();
        $event->setData($payload);
    }
}