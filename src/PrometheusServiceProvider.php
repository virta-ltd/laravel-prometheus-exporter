<?php

namespace Mcoirault\LaravelPrometheusExporter;

use ArrayAccess;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Override;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Adapter;
use Webmozart\Assert\Assert;

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

        $collectorClasses = Config::get('prometheus.collectors');

        if (!is_array($collectorClasses)) {
            throw new BindingResolutionException('PrometheusCollectors must be an array.');
        }

        foreach ($collectorClasses as $class) {
            Assert::classExists($class, "Invalid PrometheusCollector specified.");
            Assert::implementsInterface($class, CollectorRegistry::class);
            $collector = $this->app->make($class);
            Assert::implementsInterface($collector, CollectorInterface::class);
            Assert::isInstanceOf($collector, CollectorRegistry::class);
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
            function (ArrayAccess $app) {
                $adapter = $app['prometheus.storage_adapter'];
                Assert::isInstanceOf($adapter, Adapter::class);
                $prometheus = new CollectorRegistry($adapter);
                $namespace  = Config::get('prometheus.namespace');
                Assert::stringNotEmpty($namespace, 'Prometheus namespace cannot be empty.');
                return new PrometheusExporter($namespace, $prometheus);
            }
        );
        $this->app->alias(PrometheusExporter::class, 'prometheus');

        $this->app->bind('prometheus.storage_adapter_factory', fn() => new StorageAdapterFactory());

        $this->app->bind(
            Adapter::class,
            function (ArrayAccess $app) {
            /** @var StorageAdapterFactory $factory */
                $factory = $app['prometheus.storage_adapter_factory'];
                $driver  = Config::get('prometheus.storage_adapter');
                Assert::stringNotEmpty($driver, 'Prometheus storage adapter driver cannot be empty.');
                $configs = Config::get('prometheus.storage_adapters');
                Assert::isArray($configs, 'Prometheus storage adapter config must be an array.');
                $config = Arr::get($configs, $driver, []);
                Assert::isArray($config, 'Prometheus storage_adapters must be an array.');
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
