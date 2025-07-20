<?php

declare(strict_types=1);

namespace Alcoline\Core\Security\Service;

use Alcoline\Core\Exceptions\LoginRateLimitException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class LoginLimiter
{
    public function __construct(
        private RateLimiterFactory $loginLimiter,
        private RequestStack $requestStack,
    ) {}

    public function canLogin(string $key): bool
    {
        $limiter = $this->loginLimiter->create($key);
        return $limiter->consume()->isAccepted();
    }

    /**
     * @throws LoginRateLimitException
     */
    public function checkIp(): void
    {
        $clientIp = $this->requestStack->getCurrentRequest()?->getClientIp();
        if ($clientIp && !$this->canLogin($clientIp)) {
            throw new LoginRateLimitException();
        }
    }
}
