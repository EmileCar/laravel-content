# Laravel Content CMS Package

A lightweight Content Management System package for Laravel that provides a flexible way to manage page content through JSON structures.

## Features

- **Flexible Content Structure**: Store content as JSON with customizable blocks
- **Multi-language Support**: Built-in locale support for internationalization
- **Version Control**: Optimistic concurrency control to prevent conflicts
- **JSON Schema Validation**: Validate content structure against predefined schemas
- **REST API**: Complete CRUD operations via API endpoints
- **Caching**: Request-level and Laravel cache integration for performance
- **Import/Export**: Backup and restore functionality
- **Blade Components**: Easy content display in your views
- **Web-based Editor**: Rich visual editor for managing content without coding

## Installation

1. Add the package to your Laravel project's `composer.json`:

```json
{
    "require": {
        "carone/laravel-content": "*"
    }
}
```

2. Install the package:

```bash
composer install
```

3. Publish the configuration and migrations:

```bash
php artisan vendor:publish --provider="Carone\Content\ContentServiceProvider"
```

4. Run the migrations:

```bash
php artisan migrate
```

5. Publish the editor assets:

```bash
php artisan vendor:publish --provider="Carone\Content\CaroneContentServiceProvider" --tag="assets"
```

## Using the Web Editor

The package includes a rich web-based editor for managing content:

### Accessing the Editor

- **Content Dashboard:** `/admin/content/` - Lists all pages
- **Content Editor:** `/admin/content/editor/{page?}` - Edit or create pages

### Editor Features

- **Visual Block Editor:** Drag-and-drop interface for building pages
- **Pre-built Block Types:** Hero, Text, Feature Grid, Image, Footer, and Custom blocks
- **Real-time Preview:** See JSON structure as you build
- **Version Control:** Track changes with automatic versioning
- **Validation:** Real-time validation with helpful error messages

### Quick Start with Editor

1. Navigate to `/admin/content/` in your browser
2. Click "Create New Page"
3. Add blocks using the sidebar
4. Fill in content fields
5. Click "Save Changes"

For detailed editor documentation, see [EDITOR-GUIDE.md](EDITOR-GUIDE.md).

## Configuration

The package publishes a configuration file to `config/content.php`. Key settings include:

```php
return [
    // Route prefix for API endpoints
    'route_prefix' => 'admin/content',
    
    // Middleware for authorization
    'middleware' => ['auth', 'can:manage-content'],
    
    // Cache settings
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
    ],
    
    // Pagination settings
    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100,
    ],
];
```

## Usage

### API Endpoints

The package provides REST API endpoints under the configured prefix (default: `/admin/content`):

- `GET /pages` - List pages with pagination and filtering
- `POST /pages` - Create a new page
- `GET /pages/{id|name}` - Get a specific page
- `PUT/PATCH /pages/{id|name}` - Update a page
- `DELETE /pages/{id|name}` - Delete a page (soft delete)
- `GET /pages/export` - Export pages for backup
- `POST /pages/import` - Import pages from backup

### Creating Content

```php
POST /admin/content/pages
{
    "name": "home",
    "display_name": "Home Page",
    "type": "page",
    "locale": "en",
    "value": {
        "version": 1,
        "title": "Welcome Home",
        "blocks": [
            {
                "id": "hero",
                "type": "hero",
                "data": {
                    "heading": "Welcome to Our Site",
                    "subheading": "We make great things happen",
                    "cta": {
                        "text": "Get Started",
                        "url": "/start"
                    }
                }
            }
        ]
    }
}
```

### Displaying Content in Views

Use the `<x-page-content>` Blade component to display content:

```html
<!-- Display a simple value -->
<h1><x-page-content page="home" block="hero" key="heading" /></h1>

<!-- Display nested values using dot notation -->
<a href="#"><x-page-content page="home" block="hero" key="cta.url" /></a>

<!-- With default value -->
<p><x-page-content page="home" block="hero" key="description" default="No description available" /></p>

<!-- For specific locale -->
<h1><x-page-content page="home" block="hero" key="heading" locale="fr" /></h1>
```

### JSON Structure

#### Page Schema
```json
{
    "version": 1,
    "title": "Page Title",
    "locale": "en",
    "blocks": [
        {
            "id": "unique-block-id",
            "type": "block-type",
            "data": {
                // Any custom data structure
            }
        }
    ]
}
```

#### Example Content
```json
{
    "version": 1,
    "title": "Home",
    "locale": "en",
    "blocks": [
        {
            "id": "hero",
            "type": "hero",
            "data": {
                "heading": "Welcome to MySite",
                "subheading": "We make web development fun again",
                "background_image": "media://hero-bg.jpg",
                "cta": {
                    "text": "Get Started",
                    "url": "/get-started"
                }
            }
        },
        {
            "id": "features",
            "type": "feature_grid",
            "data": {
                "heading": "Why choose us?",
                "features": [
                    {
                        "icon": "fa-bolt",
                        "title": "Fast",
                        "description": "Lightning-fast load times."
                    }
                ]
            }
        }
    ]
}
```

### Programmatic Access

You can access content programmatically using the `ContentService`:

```php
use Carone\Content\Services\ContentService;

$contentService = app(ContentService::class);

// Get a page
$page = $contentService->getPageByName('home', 'en');

// Get specific block value
$heading = $contentService->getBlockValue('home', 'hero', 'heading', 'en');

// Get nested value
$ctaText = $contentService->getBlockValue('home', 'hero', 'cta.text', 'en');
```

## Authorization

Configure authorization in your `config/content.php`:

```php
'middleware' => ['auth', 'can:manage-content'],
```

You'll need to define the `manage-content` ability in your application:

```php
// In AuthServiceProvider
Gate::define('manage-content', function ($user) {
    return $user->hasRole('admin');
});
```

## Validation

The package uses JSON Schema validation to ensure content structure integrity. The schemas are located in the `schemas/` directory:

- `page.json` - Validates complete page structure
- `block.json` - Validates individual block structure

## Caching

The package implements two levels of caching:

1. **Request-level caching**: Ensures only one database query per request per page
2. **Laravel cache**: Configurable TTL-based caching for better performance

Cache is automatically cleared when content is updated.

## Import/Export

### Export
```bash
GET /admin/content/pages/export?type=page&locale=en
```

### Import
```bash
POST /admin/content/pages/import
{
    "pages": [
        // Array of page objects
    ]
}
```

## Model Structure

The `page_contents` table includes:

- `id` - Primary key
- `name` - Unique identifier for lookup
- `display_name` - Human-readable title
- `value` - JSON content
- `type` - Content type (page, fragment, block, landing)
- `locale` - Language/locale code
- `version` - Version number for optimistic concurrency
- `created_at`, `updated_at` - Timestamps
- `deleted_at` - Soft delete timestamp

## License

This package is open-sourced software licensed under the MIT license.