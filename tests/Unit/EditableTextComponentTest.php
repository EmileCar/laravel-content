<?php

namespace Carone\Content\Tests\Unit;

use Carone\Content\Models\PageContent;
use Carone\Content\Tests\TestCase;
use Carone\Content\View\Components\EditableText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class EditableTextComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set default config for tests
        config(['content.defaults.text' => 'Default Text']);
    }

    /** @test */
    public function it_renders_with_content_from_database()
    {
        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'test-element',
            'locale' => 'en',
            'type' => 'text',
            'value' => 'Database Content',
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableText('test-element');
        $view = $component->render();

        $this->assertInstanceOf(\Illuminate\Contracts\View\View::class, $view);
        $this->assertEquals('Database Content', $view->getData()['value']);
    }

    /** @test */
    public function it_renders_with_default_when_no_content_exists()
    {
        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableText('non-existent-element');
        $view = $component->render();

        $this->assertEquals('Default Text', $view->getData()['value']);
    }

    /** @test */
    public function it_shows_authenticated_status()
    {
        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        // Test unauthenticated (don't call actingAs with null)
        $component = new EditableText('test-element');
        $view = $component->render();

        $this->assertFalse($view->getData()['authenticated']);

        // Test authenticated
        $user = new \Illuminate\Foundation\Auth\User();
        $user->id = 1;
        $this->actingAs($user);

        $component = new EditableText('test-element');
        $view = $component->render();

        $this->assertTrue($view->getData()['authenticated']);
    }

    /** @test */
    public function it_passes_element_id_to_view()
    {
        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableText('my-element');
        $view = $component->render();

        $this->assertEquals('my-element', $view->getData()['elementId']);
    }

    /** @test */
    public function it_uses_configured_default_text()
    {
        config(['content.defaults.text' => 'Custom Default']);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableText('test-element');
        $view = $component->render();

        $this->assertEquals('Custom Default', $view->getData()['value']);
    }

    /** @test */
    public function it_handles_multiline_text()
    {
        $multilineText = "Line 1\nLine 2\nLine 3";

        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'multiline',
            'locale' => 'en',
            'type' => 'text',
            'value' => $multilineText,
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableText('multiline');
        $view = $component->render();

        $this->assertEquals($multilineText, $view->getData()['value']);
    }

    /** @test */
    public function it_renders_content_for_specific_locale()
    {
        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'title',
            'locale' => 'en',
            'type' => 'text',
            'value' => 'English Title',
        ]);

        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'title',
            'locale' => 'nl',
            'type' => 'text',
            'value' => 'Nederlandse Titel',
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $componentEn = new EditableText('title', 'en');
        $componentNl = new EditableText('title', 'nl');

        $viewEn = $componentEn->render();
        $viewNl = $componentNl->render();

        $this->assertEquals('English Title', $viewEn->getData()['value']);
        $this->assertEquals('Nederlandse Titel', $viewNl->getData()['value']);
    }
}

