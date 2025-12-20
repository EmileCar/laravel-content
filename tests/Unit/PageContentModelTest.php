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
            'locale' => 'en',
            'type' => 'text',
            'value' => 'Welcome to our site',
        ]);

        $this->assertDatabaseHas('page_contents', [
            'page_id' => 'home',
            'element_id' => 'hero-title',
            'locale' => 'en',
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
            'locale' => 'en',
            'type' => 'image',
            'value' => 'images/team.jpg',
        ]);

        $this->assertDatabaseHas('page_contents', [
            'page_id' => 'about',
            'element_id' => 'team-photo',
            'locale' => 'en',
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
            'locale' => 'en',
            'type' => 'file',
            'value' => 'files/manual.pdf',
        ]);

        $this->assertDatabaseHas('page_contents', [
            'page_id' => 'downloads',
            'element_id' => 'user-manual',
            'locale' => 'en',
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
            'locale' => 'en',
            'type' => 'text',
            'value' => 'Old Title',
        ]);

        $content->update(['value' => 'New Title']);

        $this->assertDatabaseHas('page_contents', [
            'page_id' => 'home',
            'element_id' => 'hero-title',
            'locale' => 'en',
            'value' => 'New Title',
        ]);
    }

    /** @test */
    public function it_enforces_unique_page_element_and_locale_combination()
    {
        PageContent::create([
            'page_id' => 'home',
            'element_id' => 'hero-title',
            'locale' => 'en',
            'type' => 'text',
            'value' => 'First Title',
        ]);

        // This should throw an exception due to unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);

        PageContent::create([
            'page_id' => 'home',
            'element_id' => 'hero-title',
            'locale' => 'en',
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
            'locale' => 'en',
            'type' => 'text',
            'value' => 'Welcome',
        ]);

        $content = PageContent::where('page_id', 'home')
            ->where('element_id', 'hero-title')
            ->where('locale', 'en')
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
            'locale' => 'en',
            'type' => 'text',
            'value' => 'Test Value',
        ]);

        $this->assertEquals('test', $content->page_id);
        $this->assertEquals('test-element', $content->element_id);
        $this->assertEquals('en', $content->locale);
        $this->assertEquals('text', $content->type);
        $this->assertEquals('Test Value', $content->value);
    }

    /** @test */
    public function it_can_retrieve_all_content_for_a_page()
    {
        PageContent::create([
            'page_id' => 'home',
            'element_id' => 'title',
            'locale' => 'en',
            'type' => 'text',
            'value' => 'Title',
        ]);

        PageContent::create([
            'page_id' => 'home',
            'element_id' => 'subtitle',
            'locale' => 'en',
            'type' => 'text',
            'value' => 'Subtitle',
        ]);

        PageContent::create([
            'page_id' => 'about',
            'element_id' => 'description',
            'locale' => 'en',
            'type' => 'text',
            'value' => 'About us',
        ]);

        $homeContent = PageContent::where('page_id', 'home')->get();

        $this->assertCount(2, $homeContent);
    }

    /** @test */
    public function it_allows_same_element_in_different_locales()
    {
        PageContent::create([
            'page_id' => 'home',
            'element_id' => 'hero-title',
            'locale' => 'en',
            'type' => 'text',
            'value' => 'Welcome',
        ]);

        PageContent::create([
            'page_id' => 'home',
            'element_id' => 'hero-title',
            'locale' => 'nl',
            'type' => 'text',
            'value' => 'Welkom',
        ]);

        $this->assertDatabaseHas('page_contents', [
            'page_id' => 'home',
            'element_id' => 'hero-title',
            'locale' => 'en',
            'value' => 'Welcome',
        ]);

        $this->assertDatabaseHas('page_contents', [
            'page_id' => 'home',
            'element_id' => 'hero-title',
            'locale' => 'nl',
            'value' => 'Welkom',
        ]);
    }

    /** @test */
    public function it_can_filter_content_by_locale_using_scope()
    {
        PageContent::create([
            'page_id' => 'home',
            'element_id' => 'title',
            'locale' => 'en',
            'type' => 'text',
            'value' => 'English Title',
        ]);

        PageContent::create([
            'page_id' => 'home',
            'element_id' => 'title',
            'locale' => 'nl',
            'type' => 'text',
            'value' => 'Nederlandse Titel',
        ]);

        $englishContent = PageContent::forLocale('en')->get();
        $dutchContent = PageContent::forLocale('nl')->get();

        $this->assertCount(1, $englishContent);
        $this->assertCount(1, $dutchContent);
        $this->assertEquals('English Title', $englishContent->first()->value);
        $this->assertEquals('Nederlandse Titel', $dutchContent->first()->value);
    }

    /** @test */
    public function it_returns_default_locale_from_config()
    {
        config(['content.locale.default' => 'en']);

        $defaultLocale = PageContent::getDefaultLocale();

        $this->assertEquals('en', $defaultLocale);
    }

    /** @test */
    public function it_uses_app_locale_as_fallback_for_default_locale()
    {
        config(['content.locale.default' => '']);
        config(['app.locale' => 'fr']);

        $defaultLocale = PageContent::getDefaultLocale();

        $this->assertEquals('fr', $defaultLocale);
    }

    /** @test */
    public function for_locale_scope_uses_default_when_no_locale_specified()
    {
        config(['content.locale.default' => 'en']);

        PageContent::create([
            'page_id' => 'home',
            'element_id' => 'title',
            'locale' => 'en',
            'type' => 'text',
            'value' => 'English Title',
        ]);

        PageContent::create([
            'page_id' => 'home',
            'element_id' => 'subtitle',
            'locale' => 'nl',
            'type' => 'text',
            'value' => 'Nederlandse Ondertitel',
        ]);

        $content = PageContent::forLocale()->get();

        $this->assertCount(1, $content);
        $this->assertEquals('English Title', $content->first()->value);
    }
}
