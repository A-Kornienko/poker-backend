<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;


class ApiQueryParameterAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private JWTEncoderInterface $jwtEncoder
    ) {}

    /**
     * Called on every request to decide if this authenticator should be used
     */
     public function supports(Request $request): ?bool
    {
        // only support requests to the route 'sse_table_state'
        return $request->attributes->get('_route') === 'sse_table_state';
    }

    public function authenticate(Request $request): Passport
    {
        $apiToken = $request->query->get('token');

        if (null === $apiToken) {
            // Token not provided
            throw new CustomUserMessageAuthenticationException('No authentication token provided.');
        }

        try {
            // decode the JWT token to get the user information
            $data = $this->jwtEncoder->decode($apiToken);

            if (!$data || !isset($data['email'])) {
                throw new CustomUserMessageAuthenticationException('Invalid token.');
            }
            
            $userIdentifier = $data['email'];

            // use email from the token to create the UserBadge
            return new SelfValidatingPassport(new UserBadge($userIdentifier));

        } catch (JWTDecodeFailureException $e) {
            throw new CustomUserMessageAuthenticationException('Invalid token.');
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Allow the request to proceed
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            // add whatever data you want to return
            'success' => false,
            'msg'     => 'Unauthorized: Authentication required.',
            'data'    => [],
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * This method is called when an anonymous user accesses a protected page (e.g. they need to log in).
     * We can reuse the same JSON response logic as onAuthenticationFailure().
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return $this->onAuthenticationFailure($request, $authException ?? new CustomUserMessageAuthenticationException('Authentication Required'));
    }
}
