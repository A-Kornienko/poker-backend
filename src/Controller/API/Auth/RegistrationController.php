<?php

namespace App\Controller\API\Auth;

use App\Controller\BaseApiController;
use App\Entity\User;
use App\Response\UserResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends BaseApiController
{
    #[Route('/api/auth/registration', name: 'api_registration', methods: ['POST'])]
    public function index(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $this->getCollectionFromJson($request->getContent())->toArray();
        
        /** @var User $user */
        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setPassword($data['password'] ?? '');
        
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return $this->response($messages, 400);
        }

        $user->setLogin(strstr($data['email'], '@', true) ?? $data['email']);
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $data['password'] ?? ''
        );
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->response(UserResponse::item($user));
    }
}
