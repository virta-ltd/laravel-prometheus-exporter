<?php

/** @var \Illuminate\Routing\Route $route */

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Mcoirault\LaravelPrometheusExporter\MetricsController;

$route = Route::get(
    Config::get('prometheus.metrics_route_path'),
    MetricsController::class . '@getMetrics'
);

if ($name = Config::get('prometheus.metrics_route_name')) {
    $route->name($name);
}

$middleware = Config::get('prometheus.metrics_route_middleware');

if ($middleware) {
    $route->middleware($middleware);
}
