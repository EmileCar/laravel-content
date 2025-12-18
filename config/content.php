<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Table Name
    |--------------------------------------------------------------------------
    |
    | The name of the database table used to store page content.
    | You can customize this if you need a different table name.
    |
    */
    'table_name' => env('CONTENT_TABLE_NAME', 'page_contents'),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix for all content management routes. Default is 'admin/content'
    | which will result in routes like /admin/content/pages
    |
    */
    'route_prefix' => env('CONTENT_ROUTE_PREFIX', 'admin/content'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware to apply to all content management routes. This should
    | include authentication and authorization middleware to ensure only
    | authorized users can manage content.
    |
    | Example: ['auth', 'can:manage-content']
    | Set to empty array to disable middleware protection (not recommended)
    |
    */
    'middleware' => [
        'web',
        'auth',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    |
    | Default values shown when content is not yet defined
    |
    */
    'defaults' => [
        'text' => env('CONTENT_DEFAULT_TEXT', '-- No content available --'),
        'image' => env('CONTENT_DEFAULT_IMAGE', 'images/placeholder.png'),
        'file' => env('CONTENT_DEFAULT_FILE', 'files/placeholder.pdf'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Settings for caching page content to improve performance.
    | When enabled, content will be cached for faster retrieval.
    |
    */
    'cache' => [
        'enabled' => env('CONTENT_CACHE_ENABLED', true),
        'ttl' => env('CONTENT_CACHE_TTL', 3600), // Time to live in seconds (1 hour)
        'key_prefix' => env('CONTENT_CACHE_PREFIX', 'laravel_content_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Settings
    |--------------------------------------------------------------------------
    |
    | Configure multi-language support for your content.
    | The default locale will be used when no locale is specified.
    | Available locales determines which languages can be used in the editor.
    |
    */
    'locale' => [
        'enabled' => env('CONTENT_LOCALE_ENABLED', true),
        'default' => env('CONTENT_DEFAULT_LOCALE', 'en'),
        'fallback' => env('CONTENT_FALLBACK_LOCALE_ENABLED', true),
        'available' => [
            'en' => 'English',
            'nl' => 'Nederlands',
            'fr' => 'Français',
            'de' => 'Deutsch',
            'es' => 'Español',
            // Add more locales as needed
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Types
    |--------------------------------------------------------------------------
    |
    | Allowed content types that can be stored in the database.
    | You can extend this array if you need additional content types.
    |
    */
    'content_types' => [
        'text',
        'image',
        'file',
    ],

    /*
    |--------------------------------------------------------------------------
    | Editor Visibility
    |--------------------------------------------------------------------------
    |
    | Controls when editable components display edit indicators.
    | Set to 'always' to show for all authenticated users,
    | or specify a gate/ability to check for specific permissions.
    |
    */
    'editor_visibility' => [
        'enabled' => env('CONTENT_EDITOR_ENABLED', true),
        'gate' => env('CONTENT_EDITOR_GATE', null), // e.g., 'manage-content'
    ],
];
