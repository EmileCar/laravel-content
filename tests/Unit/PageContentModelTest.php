<?php

namespace Carone\Content\Tests\Unit;

use Carone\Content\Models\PageContent;
use Carone\Content\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PageContentModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_text_content()
    {
        $content = PageContent::create([
            'page_id' => 'home',
            'element_id' => 'hero-title',
            'type' => 'text',
            'value' => 'Welcome to our site',
        ]);

        $this->assertDatabaseHas('page_contents', [
            'page_id' => 'home',
            'element_id' => 'hero-title',
            'type' => 'text',
            'value' => 'Welcome to our site',
        ]);
    }

    /** @test */
    public function it_can_create_image_content()
    {
        $content = PageContent::create([
            'page_id' => 'about',
            'element_id' => 'team-photo',
            'type' => 'image',
            'value' => 'images/team.jpg',
        ]);

        $this->assertDatabaseHas('page_contents', [
            'page_id' => 'about',
            'element_id' => 'team-photo',
            'type' => 'image',
            'value' => 'images/team.jpg',
        ]);
    }

    /** @test */
    public function it_can_create_file_content()
    {
        $content = PageContent::create([
            'page_id' => 'downloads',
            'element_id' => 'user-manual',
            'type' => 'file',
            'value' => 'files/manual.pdf',
        ]);

        $this->assertDatabaseHas('page_contents', [
            'page_id' => 'downloads',
            'element_id' => 'user-manual',
            'type' => 'file',
            'value' => 'files/manual.pdf',
        ]);
    }

    /** @test */
    public function it_can_update_content()
    {
        $content = PageContent::create([
            'page_id' => 'home',
            'element_id' => 'hero-title',
            'type' => 'text',
            'value' => 'Old Title',
        ]);

        $content->update(['value' => 'New Title']);

        $this->assertDatabaseHas('page_contents', [
            'page_id' => 'home',
            'element_id' => 'hero-title',
            'value' => 'New Title',
        ]);
    }

    /** @test */
    public function it_enforces_unique_page_and_element_combination()
    {
        PageContent::create([
            'page_id' => 'home',
            'element_id' => 'hero-title',
            'type' => 'text',
            'value' => 'First Title',
        ]);

        // This should throw an exception due to unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);

        PageContent::create([
            'page_id' => 'home',
            'element_id' => 'hero-title',
            'type' => 'text',
            'value' => 'Second Title',
        ]);
    }

    /** @test */
    public function it_can_find_content_by_page_and_element()
    {
        PageContent::create([
            'page_id' => 'home',
            'element_id' => 'hero-title',
            'type' => 'text',
            'value' => 'Welcome',
        ]);

        $content = PageContent::where('page_id', 'home')
            ->where('element_id', 'hero-title')
            ->first();

        $this->assertEquals('Welcome', $content->value);
    }

    /** @test */
    public function it_uses_configured_table_name()
    {
        $model = new PageContent();
        $this->assertEquals('page_contents', $model->getTable());
    }

    /** @test */
    public function it_allows_mass_assignment_of_required_fields()
    {
        $content = new PageContent([
            'page_id' => 'test',
            'element_id' => 'test-element',
            'type' => 'text',
            'value' => 'Test Value',
        ]);

        $this->assertEquals('test', $content->page_id);
        $this->assertEquals('test-element', $content->element_id);
        $this->assertEquals('text', $content->type);
        $this->assertEquals('Test Value', $content->value);
    }

    /** @test */
    public function it_can_retrieve_all_content_for_a_page()
    {
        PageContent::create([
            'page_id' => 'home',
            'element_id' => 'title',
            'type' => 'text',
            'value' => 'Title',
        ]);

        PageContent::create([
            'page_id' => 'home',
            'element_id' => 'subtitle',
            'type' => 'text',
            'value' => 'Subtitle',
        ]);

        PageContent::create([
            'page_id' => 'about',
            'element_id' => 'description',
            'type' => 'text',
            'value' => 'About us',
        ]);

        $homeContent = PageContent::where('page_id', 'home')->get();

        $this->assertCount(2, $homeContent);
    }
}
