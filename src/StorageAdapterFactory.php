<?php

namespace Mcoirault\LaravelPrometheusExporter;

use InvalidArgumentException;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\APC;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

class StorageAdapterFactory
{
    /**
     * Factory a storage adapter.
     *
     * @param string $driver
     * @param array $config
     *
     * @return Adapter
     *
     * @throws \Prometheus\Exception\StorageException
     */
    public function make($driver, array $config = [])
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
     * @param array $config
     *
     * @return Redis
     */
    protected function makeRedisAdapter(array $config)
    {
        if (isset($config['prefix'])) {
            Redis::setPrefix($config['prefix']);
        }
        return new Redis($config);
    }
}
