<?php

namespace Tests;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Mcoirault\LaravelPrometheusExporter\CollectorInterface;
use Mcoirault\LaravelPrometheusExporter\PrometheusExporter;

class PrometheusExporterTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testConstruct()
    {
        $registry = $this->createMock(CollectorRegistry::class);
        $exporter = new PrometheusExporter('app', $registry);
        $this->assertEquals('app', $exporter->getNamespace());
        $this->assertSame($registry, $exporter->getPrometheus());
    }

    /**
     * @throws Exception
     */
    public function testConstructWithCollectors()
    {
        $collector1 = $this->createMock(CollectorInterface::class);
        $collector1->expects($this->once())->method('getName')
            ->willReturn('users');
        $collector1->expects($this->once())->method('registerMetrics')
            ->with($this->isInstanceOf(PrometheusExporter::class));
        $collector2 = $this->createMock(CollectorInterface::class);
        $collector2->expects($this->once())->method('getName')
            ->willReturn('search_requests');
        $collector2->expects($this->once())->method('registerMetrics')
            ->with($this->isInstanceOf(PrometheusExporter::class));

        $registry = $this->createMock(CollectorRegistry::class);
        $exporter = new PrometheusExporter('app', $registry, [$collector1, $collector2]);

        $collectors = $exporter->getCollectors();
        $this->assertCount(2, $collectors);
        $this->assertArrayHasKey('users', $collectors);
        $this->assertArrayHasKey('search_requests', $collectors);
        $this->assertSame($collector1, $collectors['users']);
        $this->assertSame($collector2, $collectors['search_requests']);
    }

    /**
     * @throws Exception
     */
    public function testRegisterCollector()
    {
        $registry = $this->createMock(CollectorRegistry::class);
        $exporter = new PrometheusExporter('app', $registry);

        $this->assertEmpty($exporter->getCollectors());

        $collector = $this->createMock(CollectorInterface::class);
        $collector->expects($this->once())->method('getName')
            ->willReturn('users');
        $collector->expects($this->once())->method('registerMetrics')
            ->with($exporter);

        $exporter->registerCollector($collector);

        $collectors = $exporter->getCollectors();
        $this->assertCount(1, $collectors);
        $this->assertArrayHasKey('users', $collectors);
        $this->assertSame($collector, $collectors['users']);
    }

    /**
     * @throws Exception
     */
    public function testRegisterCollectorWhenCollectorIsAlreadyRegistered()
    {
        $registry = $this->createMock(CollectorRegistry::class);
        $exporter = new PrometheusExporter('app', $registry);

        $this->assertEmpty($exporter->getCollectors());

        $collector = $this->createMock(CollectorInterface::class);
        $collector->expects($this->exactly(2))->method('getName')
            ->willReturn('users');
        $collector->expects($this->once())->method('registerMetrics')
            ->with($exporter);

        $exporter->registerCollector($collector);

        $collectors = $exporter->getCollectors();
        $this->assertCount(1, $collectors);
        $this->assertArrayHasKey('users', $collectors);
        $this->assertSame($collector, $collectors['users']);

        $exporter->registerCollector($collector);

        $collectors = $exporter->getCollectors();
        $this->assertCount(1, $collectors);
        $this->assertArrayHasKey('users', $collectors);
        $this->assertSame($collector, $collectors['users']);
    }

    /**
     * @throws Exception
     */
    public function testGetCollector()
    {
        $registry = $this->createMock(CollectorRegistry::class);
        $exporter = new PrometheusExporter('app', $registry);

        $this->assertEmpty($exporter->getCollectors());

        $collector = $this->createMock(CollectorInterface::class);
        $collector->expects($this->once())->method('getName')
            ->willReturn('users');
        $collector->expects($this->once())->method('registerMetrics')
            ->with($exporter);

        $exporter->registerCollector($collector);

        $c = $exporter->getCollector('users');
        $this->assertSame($collector, $c);
    }

    /**
     * @throws Exception
     */
    public function testGetCollectorWhenCollectorIsNotRegistered()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The collector "test" is not registered.');

        $registry = $this->createMock(CollectorRegistry::class);
        $exporter = new PrometheusExporter('app', $registry);

        $exporter->getCollector('test');
    }

    /**
     * @throws Exception
     */
    public function testRegisterCounter()
    {
        $counter = $this->createMock(Counter::class);

        $registry = $this->createMock(CollectorRegistry::class);
        $registry->expects($this->once())->method('registerCounter')
            ->with(
                'app',
                'search_requests_total',
                'The total number of search requests.',
                ['request_type'],
            )
            ->willReturn($counter);

        $exporter = new PrometheusExporter('app', $registry);

        $c = $exporter->registerCounter(
            'search_requests_total',
            'The total number of search requests.',
            ['request_type']
        );
        $this->assertSame($counter, $c);
    }

    /**
     * @throws Exception
     */
    public function testGetCounter()
    {
        $counter = $this->createMock(Counter::class);

        $registry = $this->createMock(CollectorRegistry::class);
        $registry->expects($this->once())->method('getCounter')
            ->with(
                'app',
                'search_requests_total',
            )
            ->willReturn($counter);

        $exporter = new PrometheusExporter('app', $registry);

        $c = $exporter->getCounter('search_requests_total');
        $this->assertSame($counter, $c);
    }

    /**
     * @throws Exception
     */
    public function testGetOrRegisterCounter()
    {
        $counter = $this->createMock(Counter::class);

        $registry = $this->createMock(CollectorRegistry::class);
        $registry->expects($this->once())->method('getOrRegisterCounter')
            ->with(
                'app',
                'search_requests_total',
                'The total number of search requests.',
                ['request_type'],
            )
            ->wilLReturn($counter);

        $exporter = new PrometheusExporter('app', $registry);

        $c = $exporter->getOrRegisterCounter(
            'search_requests_total',
            'The total number of search requests.',
            ['request_type']
        );
        $this->assertSame($counter, $c);
    }

    /**
     * @throws Exception
     */
    public function testRegisterGauge()
    {
        $gauge = $this->createMock(Gauge::class);

        $registry = $this->createMock(CollectorRegistry::class);
        $registry->expects($this->once())->method('registerGauge')
            ->with(
                'app',
                'users_online_total',
                'The total number of users online.',
                ['group'],
            )
            ->wilLReturn($gauge);

        $exporter = new PrometheusExporter('app', $registry);

        $g = $exporter->registerGauge(
            'users_online_total',
            'The total number of users online.',
            ['group']
        );
        $this->assertSame($gauge, $g);
    }

    /**
     * @throws Exception
     */
    public function testGetGauge()
    {
        $gauge = $this->createMock(Gauge::class);

        $registry = $this->createMock(CollectorRegistry::class);
        $registry->expects($this->once())->method('getGauge')
            ->with(
                'app',
                'users_online_total',
            )
            ->wilLReturn($gauge);

        $exporter = new PrometheusExporter('app', $registry);

        $g = $exporter->getGauge('users_online_total');
        $this->assertSame($gauge, $g);
    }

    /**
     * @throws Exception
     */
    public function testGetOrRegisterGauge()
    {
        $gauge = $this->createMock(Gauge::class);

        $registry = $this->createMock(CollectorRegistry::class);
        $registry->expects($this->once())->method('getOrRegisterGauge')
            ->with(
                'app',
                'users_online_total',
                'The total number of users online.',
                ['group'],
            )
            ->wilLReturn($gauge);

        $exporter = new PrometheusExporter('app', $registry);

        $g = $exporter->getOrRegisterGauge(
            'users_online_total',
            'The total number of users online.',
            ['group']
        );
        $this->assertSame($gauge, $g);
    }

    /**
     * @throws Exception
     */
    public function testRegisterHistogram()
    {
        $histogram = $this->createMock(Histogram::class);

        $registry = $this->createMock(CollectorRegistry::class);
        $registry->expects($this->once())->method('registerHistogram')
            ->with(
                'app',
                'response_time_seconds',
                'The response time of a request.',
                ['request_type'],
                [0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0],
            )
            ->wilLReturn($histogram);

        $exporter = new PrometheusExporter('app', $registry);

        $h = $exporter->registerHistogram(
            'response_time_seconds',
            'The response time of a request.',
            ['request_type'],
            [0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0]
        );
        $this->assertSame($histogram, $h);
    }

    /**
     * @throws Exception
     */
    public function testGetHistogram()
    {
        $histogram = $this->createMock(Histogram::class);

        $registry = $this->createMock(CollectorRegistry::class);
        $registry->expects($this->once())->method('getHistogram')
            ->with(
                'app',
                'response_time_seconds',
            )
            ->wilLReturn($histogram);

        $exporter = new PrometheusExporter('app', $registry);

        $h = $exporter->getHistogram('response_time_seconds');
        $this->assertSame($histogram, $h);
    }

    /**
     * @throws Exception
     */
    public function testGetOrRegisterHistogram()
    {
        $histogram = $this->createMock(Histogram::class);

        $registry = $this->createMock(CollectorRegistry::class);
        $registry->expects($this->once())->method('getOrRegisterHistogram')
            ->with(
                'app',
                'response_time_seconds',
                'The response time of a request.',
                ['request_type'],
                [0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0],
            )
            ->wilLReturn($histogram);

        $exporter = new PrometheusExporter('app', $registry);

        $h = $exporter->getOrRegisterHistogram(
            'response_time_seconds',
            'The response time of a request.',
            ['request_type'],
            [0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0]
        );
        $this->assertSame($histogram, $h);
    }

    /**
     * @throws Exception
     */
    public function testExport()
    {
        $samples = ['meh'];

        $registry = $this->createMock(CollectorRegistry::class);
        $registry->expects($this->once())->method('getMetricFamilySamples')
            ->wilLReturn($samples);

        $exporter = new PrometheusExporter('app', $registry);

        $collector1 = $this->createMock(CollectorInterface::class);
        $collector1->expects($this->once())->method('getName')
            ->wilLReturn('users');
        $collector1->expects($this->once())->method('registerMetrics')
            ->with($exporter);
        $collector1->expects($this->once())->method('collect');

        $exporter->registerCollector($collector1);

        $collector2 = $this->createMock(CollectorInterface::class);
        $collector2->expects($this->once())->method('getName')
            ->wilLReturn('search_requests');
        $collector2->expects($this->once())->method('registerMetrics')
            ->with($exporter);
        $collector2->expects($this->once())->method('collect');

        $exporter->registerCollector($collector2);

        $s = $exporter->export();
        $this->assertSame($samples, $s);
    }
}
