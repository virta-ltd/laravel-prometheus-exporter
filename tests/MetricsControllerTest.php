<?php

namespace Tests;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Prometheus\RenderTextFormat;
use Mcoirault\LaravelPrometheusExporter\MetricsController;
use Mcoirault\LaravelPrometheusExporter\PrometheusExporter;

class MetricsControllerTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testConstruct()
    {
        $responseFactory = $this->createMock(ResponseFactory::class);
        $exporter        = $this->createMock(PrometheusExporter::class);
        $controller      = new MetricsController($responseFactory, $exporter);
        $this->assertSame($responseFactory, $controller->getResponseFactory());
        $this->assertSame($exporter, $controller->getPrometheusExporter());
    }

    /**
     * @throws Exception
     */
    public function testGetMetrics()
    {
        $response = $this->createMock(Response::class);

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects($this->once())
            ->method('make')
            ->with(
                "\n",
                200,
                ['Content-Type' => RenderTextFormat::MIME_TYPE],
            )
            ->willReturn($response);

        $exporter = $this->createMock(PrometheusExporter::class);
        $exporter->expects($this->once())->method('export')
            ->willReturn([]);

        $controller = new MetricsController($responseFactory, $exporter);

        $r = $controller->getMetrics();
        $this->assertSame($response, $r);
    }
}
