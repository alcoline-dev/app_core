<?php

declare(strict_types=1);

namespace Alcoline\Core\Security\Listener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Alcoline\Core\Security\Authenticator\AccessTokenAuthenticator;
use Ufo\JsonRpcBundle\Controller\ApiController;
use Ufo\JsonRpcBundle\Server\RequestPrepare\RequestCarrier;
use Ufo\RpcError\RpcInvalidTokenException;
use Ufo\RpcError\RpcJsonParseException;
use Ufo\RpcError\RpcTokenNotSentException;

use function preg_match;

class SecurityListener
{
    protected Request $request;

    protected RequestEvent $event;

    protected string $excludedMethodsRegex = '';

    /**
     * @param AccessTokenAuthenticator $authenticator
     * @param RouterInterface $router
     * @param CacheInterface $cache
     * @param string[] $excludedMethods
     */
    public function __construct(
        protected AccessTokenAuthenticator $authenticator,
        protected RouterInterface $router,
        protected CacheInterface $cache,
        protected RequestCarrier $requestCarrier,
        protected array $excludedMethods = [],
    )
    {
        $this->warmCacheExclude();
    }

    protected function warmCacheExclude(): void
    {
        // Створюємо унікальний ключ кешу на основі хешу від масиву excludedMethods
        $cacheKey = 'excluded_methods_regex_' . md5(serialize($this->excludedMethods));

        // Отримуємо або створюємо регулярний вираз з кешу
        $this->excludedMethodsRegex = $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) {
                $item->expiresAfter(2629743); // Кеш на місяць, але оновиться при зміні параметрів

                // Створюємо регулярний вираз на основі excludedMethods
                $patterns = array_map(function ($pattern) {
                    return '^' . str_replace(['.', '*'], ['\.', '.*'], $pattern) . '$';
                }, $this->excludedMethods);

                return '/(' . implode('|', $patterns) . ')/';
            }
        );
    }

    /**
     * @throws RpcJsonParseException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $this->event = $event;
        $this->request = $event->getRequest();
        $apiPath = $this->router->getRouteCollection()->get(ApiController::API_ROUTE)->getPath();
        if ($this->request->getPathInfo() !== $apiPath) {
            return;
        }
        if ($this->request->getMethod() === Request::METHOD_GET) {
            return;
        }
        $this->checkSecurity();
    }

    /**
     * @throws RpcTokenNotSentException
     * @throws RpcInvalidTokenException
     */
    protected function checkSecurity(): void
    {
        if (preg_match($this->excludedMethodsRegex, $this->requestCarrier->getRequestObject()->getMethod())) {
            return;
        }
        try {
            if (!$this->authenticator->supports($this->request)) {
                throw new RpcTokenNotSentException('AccessToken not sent');
            }
            $this->authenticator->authenticate($this->request);
        } catch (AuthenticationException $e) {
            throw new RpcInvalidTokenException('Invalid AccessToken token', previous: $e);
        }
    }

}