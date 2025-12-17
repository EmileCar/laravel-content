<?php

namespace Carone\Content\Tests\Unit;

use Carone\Content\Models\PageContent;
use Carone\Content\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class GetContentHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Define a test route
        Route::get('/test-page', function () {
            return 'test';
        })->name('test.page');
    }

    /** @test */
    public function it_retrieves_content_for_current_page()
    {
        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'title',
            'type' => 'text',
            'value' => 'Test Title',
        ]);

        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'description',
            'type' => 'text',
            'value' => 'Test Description',
        ]);

        // Mock the current route
        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $content = get_content(resetCache: true);

        $this->assertEquals('Test Title', $content->get('title'));
        $this->assertEquals('Test Description', $content->get('description'));
    }

    /** @test */
    public function it_returns_empty_collection_when_no_content_exists()
    {
        Route::shouldReceive('currentRouteName')
            ->andReturn('empty.page');

        $content = get_content(resetCache: true);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $content);
        $this->assertCount(0, $content);
    }

    /** @test */
    public function it_keys_content_by_element_id()
    {
        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'hero-title',
            'type' => 'text',
            'value' => 'Welcome',
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $content = get_content(resetCache: true);

        $this->assertTrue($content->has('hero-title'));
        $this->assertEquals('Welcome', $content->get('hero-title'));
    }

    /** @test */
    public function it_only_retrieves_content_for_specified_page()
    {
        PageContent::create([
            'page_id' => 'page1',
            'element_id' => 'title',
            'type' => 'text',
            'value' => 'Page 1 Title',
        ]);

        PageContent::create([
            'page_id' => 'page2',
            'element_id' => 'title',
            'type' => 'text',
            'value' => 'Page 2 Title',
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('page1');

        $content = get_content(resetCache: true);

        $this->assertEquals('Page 1 Title', $content->get('title'));
        $this->assertCount(1, $content);
    }

    /** @test */
    public function it_handles_null_values()
    {
        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'optional-content',
            'type' => 'text',
            'value' => null,
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $content = get_content(resetCache: true);

        $this->assertNull($content->get('optional-content'));
    }

    /** @test */
    public function it_returns_null_for_non_existent_element()
    {
        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $content = get_content(resetCache: true);

        $this->assertNull($content->get('non-existent-element'));
    }

    /** @test */
    public function it_retrieves_image_paths()
    {
        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'logo',
            'type' => 'image',
            'value' => 'images/logo.png',
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $content = get_content(resetCache: true);

        $this->assertEquals('images/logo.png', $content->get('logo'));
    }
}
