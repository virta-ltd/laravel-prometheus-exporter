<?php

namespace Mcoirault\LaravelPrometheusExporter;

use InvalidArgumentException;
use Prometheus\Exception\StorageException;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\APC;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;
use Webmozart\Assert\Assert;

class StorageAdapterFactory
{
    /**
     * Factory a storage adapter.
     *
     * @param string $driver
     * @param mixed[] $config
     *
     * @return Adapter
     *
     * @throws StorageException
     */
    public function make(string $driver, array $config = []): Redis|InMemory|APC|Adapter
    {
        return match ($driver) {
            'memory' => new InMemory(),
            'redis' => $this->makeRedisAdapter($config),
            'apc' => new APC(),
            default => throw new InvalidArgumentException(sprintf('The driver [%s] is not supported.', $driver)),
        };
    }

    /**
     * Factory a redis storage adapter.
     *
     * @param mixed[] $config
     *
     * @return Redis
     */
    protected function makeRedisAdapter(array $config): Redis
    {
        if (isset($config['prefix'])) {
            Assert::string($config['prefix']);
            Redis::setPrefix($config['prefix']);
        }
        return new Redis($config);
    }
}
