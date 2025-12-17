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

        // Publish assets
        $this->publishes([
            __DIR__ . '/../resources/assets' => public_path('vendor/carone-content'),
        ], 'assets');

        $this->registerRoutes();
        $this->registerBladeComponents();
        $this->registerCommands();
    }

    protected function registerRoutes()
    {
        // Register web routes (editor page)
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });

        // Register API routes (AJAX endpoints)
        Route::group($this->apiRouteConfiguration(), function () {
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

    protected function apiRouteConfiguration()
    {
        return [
            'prefix' => 'api/' . config('content.route_prefix', 'admin/content'),
            'middleware' => array_merge(['api'], config('content.middleware', [])),
        ];
    }

    protected function registerBladeComponents()
    {
        $this->loadViewComponentsAs('editable', [
            \Carone\Content\View\Components\EditableText::class,
            \Carone\Content\View\Components\EditableImage::class,
            \Carone\Content\View\Components\EditableFile::class,
        ]);
    }

    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Carone\Content\Console\Commands\CreateContentCommand::class,
                \Carone\Content\Console\Commands\ClearContentCommand::class,
                \Carone\Content\Console\Commands\ListContentCommand::class,
            ]);
        }
    }
}