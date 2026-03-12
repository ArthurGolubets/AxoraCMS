<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Panel Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your admin panel.
    | It will be displayed in page titles and header.
    |
    */

    'name' => env('HOLART_CMS_NAME', 'HolartCMS'),

    /*
    |--------------------------------------------------------------------------
    | Theme Color
    |--------------------------------------------------------------------------
    |
    | The primary color for the admin panel theme.
    | Default: 'red'
    |
    */

    'theme_color' => env('HOLART_CMS_THEME_COLOR', 'red'),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | This value is the prefix for all admin panel routes.
    | Default: 'admin'
    |
    */

    'route_prefix' => env('HOLART_CMS_PREFIX', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware that should be applied to admin routes.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Admin Guard
    |--------------------------------------------------------------------------
    |
    | The authentication guard for administrators.
    |
    */

    'guard' => 'admin',

    /*
    |--------------------------------------------------------------------------
    | Admin Model
    |--------------------------------------------------------------------------
    |
    | The model used for administrator authentication.
    |
    */

    'admin_model' => \HolartWeb\HolartCMS\Models\TAdministrator::class,

    /*
    |--------------------------------------------------------------------------
    | Show Modules Page
    |--------------------------------------------------------------------------
    |
    | Control whether the modules management page is accessible.
    | Set to false to hide the modules page from menu and return 404.
    |
    */

    'show_modules' => env('HOLART_CMS_SHOW_MODULES', false),

];
