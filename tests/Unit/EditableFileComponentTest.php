<?php

namespace Carone\Content\Tests\Unit;

use Carone\Content\Models\PageContent;
use Carone\Content\Tests\TestCase;
use Carone\Content\View\Components\EditableFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class EditableFileComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set default config for tests
        config(['content.defaults.file' => 'files/default.pdf']);
    }

    /** @test */
    public function it_renders_with_file_from_database()
    {
        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'contract',
            'type' => 'file',
            'value' => 'files/contract.pdf',
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableFile('contract');
        $view = $component->render();

        $this->assertEquals('files/contract.pdf', $view->getData()['value']);
    }

    /** @test */
    public function it_renders_with_default_when_no_file_exists()
    {
        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableFile('non-existent-file');
        $view = $component->render();

        $this->assertEquals('files/default.pdf', $view->getData()['value']);
    }

    /** @test */
    public function it_shows_authenticated_status()
    {
        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        // Test unauthenticated
        $component = new EditableFile('test-file');
        $view = $component->render();

        $this->assertFalse($view->getData()['authenticated']);

        // Test authenticated
        $user = new \Illuminate\Foundation\Auth\User();
        $user->id = 1;
        $this->actingAs($user);

        clear_static_content_cache();

        $component = new EditableFile('test-file');
        $view = $component->render();

        $this->assertTrue($view->getData()['authenticated']);
    }

    /** @test */
    public function it_passes_element_id_to_view()
    {
        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableFile('terms-of-service');
        $view = $component->render();

        $this->assertEquals('terms-of-service', $view->getData()['elementId']);
    }

    /** @test */
    public function it_uses_configured_default_file()
    {
        config(['content.defaults.file' => 'files/custom-default.docx']);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableFile('test-file');
        $view = $component->render();

        $this->assertEquals('files/custom-default.docx', $view->getData()['value']);
    }

    /** @test */
    public function it_handles_absolute_file_paths()
    {
        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'document',
            'type' => 'file',
            'value' => '/storage/files/document.pdf',
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableFile('document');
        $view = $component->render();

        $this->assertEquals('/storage/files/document.pdf', $view->getData()['value']);
    }

    /** @test */
    public function it_handles_external_file_urls()
    {
        PageContent::create([
            'page_id' => 'test.page',
            'element_id' => 'external-file',
            'type' => 'file',
            'value' => 'https://example.com/document.pdf',
        ]);

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        $component = new EditableFile('external-file');
        $view = $component->render();

        $this->assertEquals('https://example.com/document.pdf', $view->getData()['value']);
    }

    /** @test */
    public function it_handles_different_file_types()
    {
        $fileTypes = [
            'pdf' => 'files/document.pdf',
            'docx' => 'files/document.docx',
            'xlsx' => 'files/spreadsheet.xlsx',
            'zip' => 'files/archive.zip',
        ];

        foreach ($fileTypes as $type => $path) {
            PageContent::create([
                'page_id' => 'test.page',
                'element_id' => "file-{$type}",
                'type' => 'file',
                'value' => $path,
            ]);
        }

        Route::shouldReceive('currentRouteName')
            ->andReturn('test.page');

        foreach ($fileTypes as $type => $path) {
            clear_static_content_cache();
            
            $component = new EditableFile("file-{$type}");
            $view = $component->render();

            $this->assertEquals($path, $view->getData()['value']);
        }
    }
}
