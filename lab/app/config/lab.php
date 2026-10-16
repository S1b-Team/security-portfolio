<?php

return [
    /*
    | When FIX=0, security controls are skipped (intentionally vulnerable stage).
    | Any other value (default) enables secure-by-default behavior.
    */
    'fix_enabled' => env('FIX', '1') !== '0',

    'webhook_token' => env('WEBHOOK_TOKEN', 'lab-webhook-token-change-me'),

    'webhook_allow_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('WEBHOOK_ALLOW_HOSTS', 'example.com,httpbin.org'))
    ))),

    'upload_allowed_extensions' => ['pdf', 'png', 'jpg', 'jpeg', 'csv', 'txt'],

    'upload_allowed_mimes' => [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'text/csv',
        'text/plain',
    ],
];
