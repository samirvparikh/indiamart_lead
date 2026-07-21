<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TorqCRM Application Settings
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'Torq CRM'),

    'lead_number_prefix' => env('LEAD_NUMBER_PREFIX', 'LD'),

    'default_country' => env('DEFAULT_COUNTRY', 'India'),

    'dashboard_refresh_interval' => (int) env('DASHBOARD_REFRESH_INTERVAL', 30),

    'pagination_per_page' => (int) env('PAGINATION_PER_PAGE', 25),

];
