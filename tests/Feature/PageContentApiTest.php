<?php

namespace Carone\Content\Tests\Feature;

use Carone\Content\Tests\TestCase;
use Carone\Content\Models\PageContent;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PageContentApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_page()
    {
        $data = [
            'name' => 'test-page',
            'display_name' => 'Test Page',
            'type' => 'page',
            'locale' => 'en',
            'value' => [
                'version' => 1,
                'title' => 'Test Page Title',
                'blocks' => [
                    [
                        'id' => 'hero',
                        'type' => 'hero',
                        'data' => [
                            'heading' => 'Welcome',
                            'subheading' => 'Test content'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/admin/content/pages', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('page_contents', [
            'name' => 'test-page',
            'display_name' => 'Test Page'
        ]);
    }

    /** @test */
    public function it_can_retrieve_a_page()
    {
        $page = PageContent::create([
            'name' => 'test-page',
            'display_name' => 'Test Page',
            'type' => 'page',
            'locale' => 'en',
            'value' => [
                'blocks' => [
                    [
                        'id' => 'hero',
                        'type' => 'hero',
                        'data' => ['heading' => 'Welcome']
                    ]
                ]
            ]
        ]);

        $response = $this->getJson('/admin/content/pages/' . $page->id);

        $response->assertStatus(200)
                ->assertJson([
                    'name' => 'test-page',
                    'display_name' => 'Test Page'
                ]);
    }

    /** @test */
    public function it_validates_json_schema()
    {
        $data = [
            'name' => 'invalid-page',
            'display_name' => 'Invalid Page',
            'value' => [
                'blocks' => [
                    // Missing required 'id' field
                    [
                        'type' => 'hero',
                        'data' => ['heading' => 'Welcome']
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/admin/content/pages', $data);

        $response->assertStatus(422);
    }
}