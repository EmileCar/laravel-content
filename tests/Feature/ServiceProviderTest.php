<?php

namespace Carone\Content\Tests\Feature;

use Carone\Content\CaroneContentServiceProvider;
use Carone\Content\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ServiceProviderTest extends TestCase
{
    /** @test */
    public function it_registers_configuration()
    {
        $this->assertNotNull(config('content'));
        $this->assertIsArray(config('content'));
    }

    /** @test */
    public function it_has_default_configuration_values()
    {
        $this->assertEquals('page_contents', config('content.table_name'));
        $this->assertEquals('admin/content', config('content.route_prefix'));
        $this->assertIsArray(config('content.middleware'));
        $this->assertTrue(is_bool(config('content.cache.enabled')));
    }

    /** @test */
    public function it_loads_migrations()
    {
        $migrations = File::files(__DIR__ . '/../../database/migrations');

        $this->assertNotEmpty($migrations);
        $this->assertTrue(
            collect($migrations)->contains(function ($file) {
                return str_contains($file->getFilename(), 'create_page_contents_table');
            })
        );
    }

    /** @test */
    public function it_loads_views()
    {
        $this->assertTrue(
            view()->exists('laravel-content::components.editable-text')
        );

        $this->assertTrue(
            view()->exists('laravel-content::components.editable-image')
        );

        $this->assertTrue(
            view()->exists('laravel-content::components.editable-file')
        );
    }

    /** @test */
    public function it_registers_helper_function()
    {
        $this->assertTrue(function_exists('get_content'));
    }

    /** @test */
    public function config_has_all_required_keys()
    {
        $config = config('content');

        $this->assertArrayHasKey('table_name', $config);
        $this->assertArrayHasKey('route_prefix', $config);
        $this->assertArrayHasKey('middleware', $config);
        $this->assertArrayHasKey('defaults', $config);
        $this->assertArrayHasKey('cache', $config);
        $this->assertArrayHasKey('content_types', $config);
    }

    /** @test */
    public function cache_configuration_has_required_keys()
    {
        $cache = config('content.cache');

        $this->assertArrayHasKey('enabled', $cache);
        $this->assertArrayHasKey('ttl', $cache);
        $this->assertArrayHasKey('key_prefix', $cache);
    }

    /** @test */
    public function defaults_configuration_has_required_keys()
    {
        $defaults = config('content.defaults');

        $this->assertArrayHasKey('text', $defaults);
        $this->assertArrayHasKey('image', $defaults);
        $this->assertArrayHasKey('file', $defaults);
    }

    /** @test */
    public function content_types_are_configured()
    {
        $types = config('content.content_types');

        $this->assertIsArray($types);
        $this->assertContains('text', $types);
        $this->assertContains('image', $types);
        $this->assertContains('file', $types);
    }
}
