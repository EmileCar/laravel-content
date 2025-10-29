<?php

namespace Carone\Content\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Carone\Content\ContentServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            ContentServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}