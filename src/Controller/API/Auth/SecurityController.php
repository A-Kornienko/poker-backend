<?php

namespace App\Controller\API\Auth;

use App\Controller\BaseApiController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class SecurityController extends BaseApiController
{
    #[Route('/api/auth/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // This code will not be executed. 
        // Symfony Security Event Listener will intercept this request and process the output.
        return new JsonResponse([
            'message' => 'Successfully logged out',
            'status' => 'success'
        ], 200);
    }
}