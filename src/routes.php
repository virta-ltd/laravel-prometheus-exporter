<?php

/** @var \Illuminate\Routing\Route $route */

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Mcoirault\LaravelPrometheusExporter\MetricsController;
use Webmozart\Assert\Assert;

Assert::string(Config::get('prometheus.metrics_route_path'));
Assert::string(Config::get('prometheus.metrics_route_name'));
$route = Route::get(
    Config::get('prometheus.metrics_route_path'),
    MetricsController::class . '@getMetrics'
);

if ($name = Config::get('prometheus.metrics_route_name')) {
    $route->name($name);
}

/** @var string|string[]|null $middleware */
$middleware = Config::get('prometheus.metrics_route_middleware');

if (is_string($middleware) || is_array($middleware)) {
    $route->middleware($middleware);
}
