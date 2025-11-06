<?php

namespace App\Controller\API\Auth;

use App\Controller\BaseApiController;
use App\Response\UserResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends BaseApiController
{
    #[Route('/api/auth/user', name: 'api_user', methods: ['POST'])]
    public function index(): JsonResponse
    {
        return $this->response(UserResponse::item($this->getUser()));
    }
}
