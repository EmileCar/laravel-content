# Laravel Content Manager

A lightweight, developer-friendly content management package for Laravel applications. This is **not a full-fledged CMS**, but rather a simple solution for managing editable text and images directly in your views, perfect for quick content updates and typo fixes without deploying new code.

## Features

- 🚀 **Simple Integration** - Drop-in Blade components for editable content
- 📝 **Text, Image & File Support** - Manage text content, image paths, and file downloads
- 🎨 **Built-in Visual Editor** - Clean, modern interface for managing all your content
- 🔒 **Authentication Aware** - Only show edit indicators to authenticated users
- ⚡ **Performance Optimized** - Built-in caching support for fast content retrieval
- 🎯 **Customizable** - Extensive configuration options
- 🔧 **Developer-Friendly** - Minimal setup, maximum flexibility

## Installation

Install the package via Composer:

```bash
composer require carone/laravel-content
```

### Publish Configuration

Publish the configuration file to customize the package settings:

```bash
php artisan vendor:publish --provider="Carone\Content\CaroneContentServiceProvider" --tag="config"
```

This creates a `config/content.php` file where you can configure routes, middleware, caching, and default values.

### Run Migrations

Publish and run the migrations to create the `page_contents` table:

```bash
php artisan vendor:publish --provider="Carone\Content\CaroneContentServiceProvider" --tag="migrations"
php artisan migrate
```

### Publish Views (Optional)

If you need to customize the component views:

```bash
php artisan vendor:publish --provider="Carone\Content\CaroneContentServiceProvider" --tag="views"
```

## Usage

### Basic Text Content

Use the `editable-text` component to create editable paragraphs:

```blade
<x-editable-text element="about-me-text" />
```

With custom classes and attributes:

```blade
<x-editable-text element="hero-title" class="text-4xl font-bold text-gray-900" />
```

### Image Content

Use the `editable-image` component for editable images:

```blade
<x-editable-image element="company-logo" />
```

With custom attributes:

```blade
<x-editable-image element="hero-banner" class="w-full h-64 object-cover" alt="Hero Banner" />
```

### File Downloads

Use the `editable-file` component for downloadable files:

```blade
<x-editable-file element="terms-and-conditions" />
```

With custom link text:

```blade
<x-editable-file element="user-manual" text="Download User Manual" class="btn btn-primary" />
```

By default, the component displays the filename as link text. Use the `text` attribute to customize it.

## Content Editor

The package includes a beautiful, built-in visual editor for managing all your content.

### Accessing the Editor

Navigate to the editor at:

```
https://yoursite.com/admin/content
```

(The prefix is configurable via `CONTENT_ROUTE_PREFIX`)

### Editor Features

- **📋 Page List** - View all pages with content in a clean sidebar
- **✏️ Inline Editing** - Edit content values directly with instant save
- **➕ Add Content** - Add new content elements with helpful reminders
- **🗑️ Delete Content** - Remove unwanted content items
- **🔍 Route Explorer** - Discover and quick-add content for any application route
- **💾 Auto-save** - Changes are saved immediately with visual feedback

### Route Explorer

The editor includes a powerful "Route Explorer" feature that helps you quickly start managing content for any page in your application:

1. Click **"Discover Routes"** at the bottom of the sidebar
2. Browse all your application's web routes
3. Click **"Quick Add"** next to any route to:
   - Add the page to your sidebar
   - Automatically open the "Add Content" modal
   - Start creating content immediately

Pages added via Route Explorer are tracked in the frontend until you save your first content item, making it easy to explore without cluttering your database.

### API Routes

The editor uses the following API endpoints (all under `/api/admin/content` by default):

- `GET /page/{pageId}` - Fetch content for a specific page
- `POST /content` - Create or update content
- `DELETE /content/{id}` - Delete content
- `GET /routes` - Get all application routes

These routes automatically include the `api` middleware and can be further protected via your config.

### Editor Access Control

By default, the editor is protected by the middleware defined in your config:

```php
'middleware' => [
    'web',
    'auth',
],
```

Add additional middleware for fine-grained control:

```php
'middleware' => [
    'web',
    'auth',
    'can:manage-content',  // Require specific permission
],
```

### How It Works

1. **Define Content Areas** - Add `<x-editable-text>`, `<x-editable-image>`, or `<x-editable-file>` components in your views with unique element identifiers
2. **View Your Pages** - Content displays with default values when not yet defined
3. **Edit Content** - Use the visual editor to update content or authenticated users see edit indicators on the frontend
4. **Store in Database** - Content is stored per page and element combination

### Content Retrieval

The package provides a `get_content()` helper function that retrieves all content for the current route:

```php
$content = get_content();
$value = $content->get('about-me-text');
```

Content is automatically cached based on your configuration settings for optimal performance.

## Configuration

The `config/content.php` file provides extensive customization options:

### Table Name

Customize the database table name:

```php
'table_name' => env('CONTENT_TABLE_NAME', 'page_contents'),
```

### Route Configuration

Configure the admin routes prefix:

```php
'route_prefix' => env('CONTENT_ROUTE_PREFIX', 'admin/content'),
```

### Middleware Protection

Protect your content management routes with middleware:

```php
'middleware' => [
    'web',
    'auth',
    // Add custom middleware like 'can:manage-content'
],
```

### Default Values

Set default text, images, and files when content is not yet defined:

```php
'defaults' => [
    'text' => env('CONTENT_DEFAULT_TEXT', '-- No content available --'),
    'image' => env('CONTENT_DEFAULT_IMAGE', 'images/placeholder.png'),
    'file' => env('CONTENT_DEFAULT_FILE', 'files/placeholder.pdf'),
],
```

### Caching

Enable or disable caching and configure cache duration:

```php
'cache' => [
    'enabled' => env('CONTENT_CACHE_ENABLED', true),
    'ttl' => env('CONTENT_CACHE_TTL', 3600), // 1 hour
    'key_prefix' => env('CONTENT_CACHE_PREFIX', 'laravel_content_'),
],
```

### Environment Variables

You can set these in your `.env` file:

```env
CONTENT_TABLE_NAME=page_contents
CONTENT_ROUTE_PREFIX=admin/content
CONTENT_DEFAULT_TEXT="Content coming soon..."
CONTENT_DEFAULT_IMAGE=images/default.png
CONTENT_DEFAULT_FILE=files/default.pdf
CONTENT_CACHE_ENABLED=true
CONTENT_CACHE_TTL=7200
```

## Database Structure

The package creates a `page_contents` table with the following structure:

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| page_id | string | Route name or page identifier |
| element_id | string | Unique element identifier |
| type | enum | Content type ('text', 'image', or 'file') |

## API Routes

The package registers the following routes (under your configured prefix):

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/pages` | List all pages with content |
| POST | `/pages` | Create/update page content |
| GET | `/pages/{page}` | Show specific page content |
| PUT/PATCH | `/pages/{page}` | Update page content |
| DELETE | `/pages/{page}` | Delete page content |

## Use Cases

Perfect for:

- ✅ Landing pages with editable hero sections
- ✅ About pages with team member bios and photos
- ✅ Contact information that needs occasional updates
- ✅ Feature descriptions and marketing copy
- ✅ Footer content and legal text
- ✅ Downloadable files (PDFs, documents, etc.)
- ✅ Quick typo fixes without redeployment

Not ideal for:

- ❌ Complex content hierarchies
- ❌ Multi-language content (no built-in i18n)
- ❌ Rich text editing with formatting
- ❌ Actual file uploads (only stores paths/URLs)
- ❌ Content versioning and workflows

## Requirements

- PHP 8.2 or higher
- Laravel 12.0 or higher

## Security

The package is designed to work with Laravel's built-in authentication. The editable indicators only show to authenticated users via `auth()->check()`. 

**Important**: Ensure you protect your content management routes with appropriate middleware to prevent unauthorized access.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Author

**Caron Emile**  
Email: emile.caron@constructiv.be

## Support

For issues, questions, or suggestions, please open an issue on GitHub.

---

**Note**: This package provides the foundation for content management but does not include an admin UI for editing content. You'll need to implement your own editor interface or integrate with your existing admin panel.
