<?php
/**
 * Example implementation in a Laravel application
 * 
 * This file shows how to integrate the Laravel Content CMS package
 * into your Laravel application.
 */

// 1. First, register the service provider in config/app.php (if not using auto-discovery):
/*
'providers' => [
    // Other providers...
    Carone\Content\ContentServiceProvider::class,
],
*/

// 2. Define authorization gates in App\Providers\AuthServiceProvider:
/*
use Illuminate\Support\Facades\Gate;

public function boot()
{
    $this->registerPolicies();

    // Define the manage-content gate
    Gate::define('manage-content', function ($user) {
        return $user->hasRole('admin') || $user->hasPermission('manage-content');
    });
}
*/

// 3. Create a controller to handle content display in your application:

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carone\Content\Services\ContentService;

class PageController extends Controller
{
    public function __construct(private ContentService $contentService)
    {
    }

    public function home()
    {
        // The content will be automatically loaded by the x-page-content component
        return view('pages.home');
    }

    public function show($slug)
    {
        // Get page content to check if it exists
        $page = $this->contentService->getPageByName($slug);
        
        if (!$page) {
            abort(404);
        }

        return view('pages.show', compact('page'));
    }
}

// 4. Example Blade template (resources/views/pages/home.blade.php):
/*
@extends('layouts.app')

@section('content')
    <section class="hero">
        <div class="hero-content">
            <h1><x-page-content page="home" block="hero" key="heading" default="Welcome" /></h1>
            <p><x-page-content page="home" block="hero" key="subheading" default="Great things happen here" /></p>
            <a href="{{ <x-page-content page="home" block="hero" key="cta.url" default="#" /> }}" class="btn">
                <x-page-content page="home" block="hero" key="cta.text" default="Learn More" />
            </a>
        </div>
    </section>

    <section class="features">
        <h2><x-page-content page="home" block="features" key="heading" default="Features" /></h2>
        <!-- Feature items would typically be rendered via custom logic or additional components -->
    </section>
@endsection
*/

// 5. Example routes (routes/web.php):
/*
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/{slug}', [PageController::class, 'show'])->name('page.show');

// Admin routes for content management
Route::middleware(['auth', 'can:manage-content'])->group(function () {
    Route::get('/admin/content', function () {
        return view('admin.content.index');
    })->name('admin.content.index');
});
*/

// 6. Example API usage for creating content:
/*
use Carone\Content\Models\PageContent;

// Create a new page
$page = PageContent::create([
    'name' => 'about',
    'display_name' => 'About Us',
    'type' => 'page',
    'locale' => 'en',
    'value' => [
        'version' => 1,
        'title' => 'About Our Company',
        'blocks' => [
            [
                'id' => 'intro',
                'type' => 'text',
                'data' => [
                    'heading' => 'About Us',
                    'content' => 'We are a company that...',
                ]
            ],
            [
                'id' => 'team',
                'type' => 'team_grid',
                'data' => [
                    'heading' => 'Our Team',
                    'members' => [
                        [
                            'name' => 'John Doe',
                            'title' => 'CEO',
                            'bio' => 'John has been...'
                        ]
                    ]
                ]
            ]
        ]
    ]
]);
*/

// 7. Example of programmatic content access:
/*
use Carone\Content\Services\ContentService;

$contentService = app(ContentService::class);

// Get content values
$title = $contentService->getBlockValue('home', 'hero', 'heading');
$ctaUrl = $contentService->getBlockValue('home', 'hero', 'cta.url');
$features = $contentService->getBlockValue('home', 'features', 'features'); // Array of features

// Check if content exists
$page = $contentService->getPageByName('about');
if ($page) {
    $aboutContent = $page->getBlockValue('intro', 'content');
}
*/