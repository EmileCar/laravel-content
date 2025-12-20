<?php

namespace Carone\Content\Tests\Unit;

use Carone\Content\Models\PageContent;
use Carone\Content\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class CachingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure cache is completely clear before each test
        Cache::flush();
    }

    protected function tearDown(): void
    {
        // Clear cache after each test
        Cache::flush();

        parent::tearDown();
    }

    /** @test */
    public function it_caches_content_when_caching_is_enabled()
    {
        config(['content.cache.enabled' => true]);
        config(['content.cache.ttl' => 3600]);
        config(['content.locale.default' => 'en']);

        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'title',
            'locale' => 'en',
            'type' => 'text',
            'value' => 'Cached Title',
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        // First call should cache the content
        $content1 = get_content();
        $this->assertEquals('Cached Title', $content1->get('title'));

        // Verify it's in cache
        $cacheKey = config('content.cache.key_prefix') . 'test.page_en';
        $this->assertTrue(Cache::has($cacheKey));

        // Second call should use cached version
        $content2 = get_content();
        $this->assertEquals('Cached Title', $content2->get('title'));
    }

    /** @test */
    public function it_does_not_cache_when_caching_is_disabled()
    {
        config(['content.cache.enabled' => false]);
        config(['content.locale.default' => 'en']);

        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'title',
            'locale' => 'en',
            'type' => 'text',
            'value' => 'Uncached Title',
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $content = get_content(resetCache: true);
        $this->assertEquals('Uncached Title', $content->get('title'));

        // Verify it's not in cache
        $cacheKey = config('content.cache.key_prefix') . 'test.page_en';
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_uses_configured_cache_ttl()
    {
        config(['content.cache.enabled' => true]);
        config(['content.cache.ttl' => 7200]); // 2 hours
        config(['content.locale.default' => 'en']);

        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'title',
            'locale' => 'en',
            'type' => 'text',
            'value' => 'Test Title',
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        get_content(resetCache: true);

        $cacheKey = config('content.cache.key_prefix') . 'test.page_en';
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_uses_configured_cache_key_prefix()
    {
        config(['content.cache.enabled' => true]);
        config(['content.cache.key_prefix' => 'custom_prefix_']);
        config(['content.locale.default' => 'en']);

        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'title',
            'locale' => 'en',
            'type' => 'text',
            'value' => 'Test Title',
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        get_content(resetCache: true);

        $this->assertTrue(Cache::has('custom_prefix_test.page_en'));
    }

    /** @test */
    public function static_cache_prevents_multiple_database_queries_in_same_request()
    {
        config(['content.cache.enabled' => false]); // Disable Laravel cache to test static cache

        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'title',
            'locale' => 'en',
            'type' => 'text',
            'value' => 'Test Title',
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        // First call
        $content1 = get_content(resetCache: true);

        // Second call in same request should use static cache
        $content2 = get_content();

        // Both should return the same collection instance
        $this->assertSame($content1, $content2);
    }
}
