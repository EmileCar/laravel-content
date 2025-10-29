<?php

namespace Carone\Content;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Carone\Content\Services\ContentService;
use Carone\Content\Services\JsonSchemaValidator;

class CaroneContentServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/content.php',
            'content'
        );

        $this->app->singleton(ContentService::class);
        $this->app->singleton(JsonSchemaValidator::class);
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../config/content.php' => config_path('content.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'migrations');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-content');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/laravel-content'),
        ], 'views');

        $this->registerRoutes();
        $this->registerBladeComponents();
    }

    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });
    }

    protected function routeConfiguration()
    {
        return [
            'prefix' => config('content.route_prefix', 'admin/content'),
            'middleware' => config('content.middleware', []),
        ];
    }

    protected function registerBladeComponents()
    {
        $this->loadViewComponentsAs('', [
            \Carone\Content\View\Components\PageContent::class,
        ]);
    }
}