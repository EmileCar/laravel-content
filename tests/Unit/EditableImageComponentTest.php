<?php

namespace Carone\Content\Tests\Unit;

use Carone\Content\Models\PageContent;
use Carone\Content\Tests\TestCase;
use Carone\Content\View\Components\EditableImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class EditableImageComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set default config for tests
        config(['content.defaults.image' => 'images/default.png']);
    }

    /** @test */
    public function it_renders_with_image_from_database()
    {
        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'logo',
            'locale' => 'en',
            'type' => 'image',
            'value' => 'images/logo.png',
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableImage('logo');
        $view = $component->render();

        $this->assertEquals('images/logo.png', $view->getData()['value']);
    }

    /** @test */
    public function it_renders_with_default_when_no_image_exists()
    {
        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableImage('non-existent-image');
        $view = $component->render();

        $this->assertEquals('images/default.png', $view->getData()['value']);
    }

    /** @test */
    public function it_shows_authenticated_status()
    {
        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        // Test unauthenticated (don't call actingAs with null)
        $component = new EditableImage('test-image');
        $view = $component->render();

        $this->assertFalse($view->getData()['authenticated']);

        // Test authenticated
        $user = new \Illuminate\Foundation\Auth\User();
        $user->id = 1;
        $this->actingAs($user);

        $component = new EditableImage('test-image');
        $view = $component->render();

        $this->assertTrue($view->getData()['authenticated']);
    }

    /** @test */
    public function it_passes_element_id_to_view()
    {
        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableImage('hero-banner');
        $view = $component->render();

        $this->assertEquals('hero-banner', $view->getData()['elementId']);
    }

    /** @test */
    public function it_uses_configured_default_image()
    {
        config(['content.defaults.image' => 'images/custom-default.jpg']);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableImage('test-image');
        $view = $component->render();

        $this->assertEquals('images/custom-default.jpg', $view->getData()['value']);
    }

    /** @test */
    public function it_handles_absolute_image_paths()
    {
        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'banner',
            'type' => 'image',
            'value' => '/storage/images/banner.jpg',
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableImage('banner');
        $view = $component->render();

        $this->assertEquals('/storage/images/banner.jpg', $view->getData()['value']);
    }

    /** @test */
    public function it_handles_external_image_urls()
    {
        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'external-image',
            'type' => 'image',
            'value' => 'https://example.com/image.jpg',
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableImage('external-image');
        $view = $component->render();

        $this->assertEquals('https://example.com/image.jpg', $view->getData()['value']);
    }
}

