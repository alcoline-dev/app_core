<?php

declare(strict_types=1);

namespace Alcoline\Core\Security\Authenticator;

use Alcoline\Core\Api\DTO\UserMeInfoView;
use Alcoline\Core\Security\Service\UserContext;
use Alcoline\Core\Security\Service\UserFetcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AccessTokenAuthenticator extends AbstractAuthenticator
{
    const string ACCESS_TOKEN = 'AccessToken';
    public function __construct(
        protected UserFetcher $userFetcher,
        protected UserContext $userContext,
        protected ValidatorInterface $validator
    ) {}

    public function supports(Request $request): ?bool
    {
        return !empty($request->headers->get(static::ACCESS_TOKEN));
    }

    public function authenticate(Request $request): Passport
    {
        $accessToken = $this->getAccessToken($request);
        $this->validateAccessToken($accessToken);
        $userMeDTO = $this->getUserFromAccessToken($accessToken);

        $this->userContext->addUser($userMeDTO);

        return new SelfValidatingPassport(
            new UserBadge($accessToken, function() use ($userMeDTO) {
                return $userMeDTO;
            })
        );
    }

    public function onAuthenticationSuccess(
        Request $request, TokenInterface $token, string $firewallName
    ): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(
        Request $request, AuthenticationException $exception
    ): ?Response
    {
        return new Response($exception->getMessageKey(), Response::HTTP_UNAUTHORIZED);
    }

    private function getAccessToken(Request $request): string
    {
        $accessToken = $request->headers->get(static::ACCESS_TOKEN) ?? '';

        if (!$accessToken) {
            throw new CustomUserMessageAuthenticationException('No access token provided');
        }

        return $accessToken;
    }

    private function validateAccessToken(string $accessToken): void
    {
        $errors = $this->validator->validate($accessToken, new Uuid());

        if (count($errors) > 0) {
            $errorMessage = $errors[0]->getMessage();
            throw new CustomUserMessageAuthenticationException($errorMessage);
        }
    }

    private function getUserFromAccessToken(string $accessToken): UserMeInfoView
    {
        try {
            $userMeDTO = $this->userFetcher->getUserFromAccessToken($accessToken);
        } catch (\Throwable) {
            throw new CustomUserMessageAuthenticationException('Invalid access token');
        }
        return $userMeDTO;
    }
}
