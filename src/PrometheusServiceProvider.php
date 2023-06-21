<?php

namespace Mcoirault\LaravelPrometheusExporter;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Adapter;

class PrometheusServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(PrometheusExporter $exporter): void
    {
        $this->publishes([
            __DIR__ . '/../config/prometheus.php' => config_path('prometheus.php'),
        ]);

        foreach (config('prometheus.collectors') as $class) {
            $collector = $this->app->make($class);
            $exporter->registerCollector($collector);
        }
    }

    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/prometheus.php', 'prometheus');

        $this->app->singleton(PrometheusExporter::class, function ($app) {
            $adapter = $app['prometheus.storage_adapter'];
            $prometheus = new CollectorRegistry($adapter);
            return new PrometheusExporter(config('prometheus.namespace'), $prometheus);
        });
        $this->app->alias(PrometheusExporter::class, 'prometheus');

        $this->app->bind('prometheus.storage_adapter_factory', function () {
            return new StorageAdapterFactory();
        });

        $this->app->bind(Adapter::class, function ($app) {
            $factory = $app['prometheus.storage_adapter_factory'];
            /** @var StorageAdapterFactory $factory */
            $driver = config('prometheus.storage_adapter');
            $configs = config('prometheus.storage_adapters');
            $config = Arr::get($configs, $driver, []);
            return $factory->make($driver, $config);
        });
        $this->app->alias(Adapter::class, 'prometheus.storage_adapter');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            Adapter::class,
            PrometheusExporter::class,
            StorageAdapterFactory::class,
            'prometheus',
            'prometheus.storage_adapter_factory',
            'prometheus.storage_adapter',
        ];
    }
}
