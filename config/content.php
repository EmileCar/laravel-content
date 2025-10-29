<?php

return [
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
    |
    */
    'middleware' => [
        'auth',
        'can:manage-content',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Settings for caching page content to improve performance
    |
    */
    'cache' => [
        'enabled' => env('CONTENT_CACHE_ENABLED', true),
        'ttl' => env('CONTENT_CACHE_TTL', 3600), // 1 hour
        'key_prefix' => 'laravel_content_',
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON Schema Validation
    |--------------------------------------------------------------------------
    |
    | Settings for JSON schema validation
    |
    */
    'validation' => [
        'strict' => env('CONTENT_VALIDATION_STRICT', true),
        'schemas_path' => __DIR__ . '/../schemas',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Default pagination settings for listing pages
    |
    */
    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100,
    ],
];