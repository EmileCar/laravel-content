<?php

namespace Carone\Content\Tests;

use Carone\Content\CaroneContentServiceProvider;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        clear_static_content_cache();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        clear_static_content_cache();
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            CaroneContentServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('content.cache.enabled', false);
    }
}
