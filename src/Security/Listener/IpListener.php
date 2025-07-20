<?php

declare(strict_types=1);

namespace Alcoline\Core\Security\Listener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class IpListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $clientIp = $request->getClientIp();

        $this->logger->info('Client IP: ' . $clientIp);
    }
}
