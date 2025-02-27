<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Prometheus\Exception\StorageException;
use Prometheus\Storage\APC;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;
use Mcoirault\LaravelPrometheusExporter\StorageAdapterFactory;

class StorageAdapterFactoryTest extends TestCase
{
    /**
     * @throws StorageException
     */
    public function testMakeMemoryAdapter(): void
    {
        $factory = new StorageAdapterFactory();
        $adapter = $factory->make('memory');
        $this->assertInstanceOf(InMemory::class, $adapter);
    }

    public function testMakeApcAdapter(): void
    {
        $factory = new StorageAdapterFactory();
        try {
            $adapter = $factory->make('apc');
        } catch (StorageException) {
            $this->markTestSkipped("APCu not enabled? Skipping test");
        }
        $this->assertInstanceOf(APC::class, $adapter);
    }

    /**
     * @throws StorageException
     */
    public function testMakeRedisAdapter(): void
    {
        $factory = new StorageAdapterFactory();
        $adapter = $factory->make('redis');
        $this->assertInstanceOf(Redis::class, $adapter);
    }

    /**
     * @throws StorageException
     */
    public function testMakeInvalidAdapter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The driver [moo] is not supported.');

        $factory = new StorageAdapterFactory();
        $factory->make('moo');
    }
}
