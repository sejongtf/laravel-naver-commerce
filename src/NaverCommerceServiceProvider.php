<?php

namespace Sejongtf\LaravelNaverCommerce;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Sejongtf\LaravelNaverCommerce\Auth\TokenManager;
use Sejongtf\LaravelNaverCommerce\Console\CategoriesExportCommand;
use Sejongtf\LaravelNaverCommerce\Console\OrdersChangedCommand;
use Sejongtf\LaravelNaverCommerce\Console\PingCommand;
use Sejongtf\LaravelNaverCommerce\Console\RequestCommand;
use Sejongtf\LaravelNaverCommerce\Console\TokenCommand;
use Sejongtf\LaravelNaverCommerce\Console\TokenForgetCommand;
use Sejongtf\LaravelNaverCommerce\Http\Client;

class NaverCommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/naver-commerce.php', 'naver-commerce');

        $this->app->singleton(TokenManager::class, function (Application $app) {
            return new TokenManager(
                $app->make(HttpFactory::class),
                $app->make(CacheFactory::class),
                $app['config']->get('naver-commerce', []),
            );
        });

        $this->app->singleton(Client::class, function (Application $app) {
            return new Client(
                $app->make(HttpFactory::class),
                $app->make(TokenManager::class),
                $app['config']->get('naver-commerce', []),
            );
        });

        $this->app->singleton(NaverCommerce::class, function (Application $app) {
            return new NaverCommerce($app->make(Client::class));
        });

        $this->app->alias(NaverCommerce::class, 'naver-commerce');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/naver-commerce.php' => $this->app->configPath('naver-commerce.php'),
            ], 'naver-commerce-config');

            $this->commands([
                TokenCommand::class,
                TokenForgetCommand::class,
                PingCommand::class,
                RequestCommand::class,
                OrdersChangedCommand::class,
                CategoriesExportCommand::class,
            ]);
        }
    }
}
