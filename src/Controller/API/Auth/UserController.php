<?php

namespace App\Controller\API\Auth;

use App\Controller\BaseApiController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Response\UserResponse;

class UserController extends BaseApiController
{
    #[Route('/api/auth/user', name: 'api_user', methods: ['GET'])]
    public function getApiUser(): JsonResponse
    {
        return $this->response(UserResponse::item($this->security->getUser()));
    }
}