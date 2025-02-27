<?php

namespace Mcoirault\LaravelPrometheusExporter;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Override;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Adapter;

class PrometheusServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Perform post-registration booting of services.
     * @throws BindingResolutionException
     */
    public function boot(PrometheusExporter $exporter): void
    {
        $this->publishes(
            [
            __DIR__ . '/../config/prometheus.php' => App::configPath('prometheus.php'),
            ]
        );

        foreach (Config::get('prometheus.collectors') as $class) {
            $collector = $this->app->make($class);
            $exporter->registerCollector($collector);
        }
    }

    /**
     * Register bindings in the container.
     */
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/prometheus.php', 'prometheus');

        $this->app->singleton(
            PrometheusExporter::class,
            function ($app) {
                $adapter    = $app['prometheus.storage_adapter'];
                $prometheus = new CollectorRegistry($adapter);
                return new PrometheusExporter(Config::get('prometheus.namespace'), $prometheus);
            }
        );
        $this->app->alias(PrometheusExporter::class, 'prometheus');

        $this->app->bind('prometheus.storage_adapter_factory', fn() => new StorageAdapterFactory());

        $this->app->bind(
            Adapter::class,
            function ($app) {
            /** @var StorageAdapterFactory $factory */
                $factory = $app['prometheus.storage_adapter_factory'];
                $driver  = Config::get('prometheus.storage_adapter');
                $configs = Config::get('prometheus.storage_adapters');
                $config  = Arr::get($configs, $driver, []);
                return $factory->make($driver, $config);
            }
        );
        $this->app->alias(Adapter::class, 'prometheus.storage_adapter');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array{class-string, class-string, class-string, string, string, string}
     */
    #[Override]
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
