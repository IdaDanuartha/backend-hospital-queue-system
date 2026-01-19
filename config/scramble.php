<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    'api_path' => 'api/v1',

    'api_domain' => null,

    'export_path' => 'api.json',

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'description' => 'RESTful API untuk sistem antrian rumah sakit dengan fitur real-time monitoring, geofencing, dan reporting yang komprehensif.',
        'title' => 'Hospital Queue Management System API',
    ],

    'ui' => [
        'title' => 'Hospital Queue API Documentation',
        'theme' => 'light',
        'hide_try_it' => false,
        'hide_schemas' => false,
        'logo' => '',
        'try_it_credentials_policy' => 'include',
        'layout' => 'responsive',
    ],

    // Update servers
    'servers' => [
        'Production' => env('APP_URL') . '/api/v1',
        'Local' => 'http://localhost:8000/api/v1',
    ],

    'enum_cases_description_strategy' => 'description',

    'enum_cases_names_strategy' => false,

    'flatten_deep_query_parameters' => true,

    'middleware' => [
        'web',
        // RestrictedDocsAccess::class,
    ],

    'extensions' => [],
];