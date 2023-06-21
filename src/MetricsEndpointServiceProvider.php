<?php

namespace Mcoirault\LaravelPrometheusExporter;

use Illuminate\Support\ServiceProvider;

class MetricsEndpointServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(PrometheusExporter $exporter)
    {
        if (config('prometheus.metrics_route_enabled')) {
            $this->loadRoutesFrom(__DIR__ . '/routes.php');
        }
    }

    /**
     * Register bindings in the container.
     */
    public function register()
    {
    }
}
