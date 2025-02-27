<?php

namespace Mcoirault\LaravelPrometheusExporter;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class MetricsEndpointServiceProvider extends ServiceProvider
{
    /**
     * Add metrics route if it should be enabled
     */
    public function boot()
    {
        if (Config::get('prometheus.metrics_route_enabled')) {
            $this->loadRoutesFrom(__DIR__ . '/routes.php');
        }
    }
}
