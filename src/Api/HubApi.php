<?php

declare(strict_types=1);

namespace Alcoline\Core\Api;

use GuzzleHttp\Exception\RequestException;
use ReflectionObject;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Throwable;
use Alcoline\Core\Contracts\ICanPing;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Pool;
use function array_merge;

class HubApi
{
    protected const int POOL_CONCURRENCY = 5;
    protected Client $client;

    /**
     * @param ICanPing[] $allSdk
     */
    public function __construct(
        #[AutowireIterator(ICanPing::CAN_PING_TAG)]
        protected iterable $allSdk,
        protected array $sdkConfigs
    ) {
        $this->client = new Client();
    }

    public function pingAll(): array
    {
        $res = [];
        foreach ($this->allSdk as $sdk) {
            $res = array_merge($res, $this->processPing($sdk));
        }
        return $res;
    }

    protected function processPing(ICanPing $sdk): array
    {
        try {
            $response = $sdk->ping();
        } catch (Throwable $e) {
            $response = $e->getMessage();
        }
        $r = new ReflectionObject($sdk);
        return [$r->getNamespaceName() => $response];
    }

    public function pingAllAsync(): array
    {
        $requests = function () {
            foreach ($this->sdkConfigs as $vendorConfig) {
                $name = $vendorConfig['name'];
                $url = rtrim($vendorConfig['url'], '/');
                $tokenKey = $vendorConfig['token_key'];
                $token = $vendorConfig['token'];

                $headers = [
                    $tokenKey => $token,
                    'Content-Type' => 'Application/json',
                ];

                $body = json_encode(['method' => 'ping']);

                $request = new Request('POST', $url, $headers, $body);

                yield $name => $request;
            }
        };

        $results = [];
        $pool = new Pool($this->client, $requests(), [
            'concurrency' => self::POOL_CONCURRENCY,
            'fulfilled' => function ($response, $name) use (&$results) {
                $results[$name] = 'PONG';
            },
            'rejected' => function ($reason, $name) use (&$results) {
                if ($reason instanceof RequestException && $reason->hasResponse()) {
                    $response = $reason->getResponse();
                    $results[$name] = (string) $response->getBody();
                } else {
                    $results[$name] = $reason instanceof \Throwable ? $reason->getMessage() : (string) $reason;
                }
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        return [
            'result' => $results
        ];
    }
}
