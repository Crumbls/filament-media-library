<?php

return [
    'disk' => env('MEDIA_LIBRARY_DISK', 'public'),

    'accepted_file_types' => ['image/*', 'video/*', 'application/pdf'],

    'max_file_size' => 10240,

    'image_conversions' => [
        'thumbnail' => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 300, 'height' => 300],
        'large' => ['width' => 1024, 'height' => 1024],
    ],

    'collections' => [],

    'models' => [
        'media' => \Crumbls\FilamentMediaLibrary\Models\Media::class,
    ],

    'routes' => [
        'enabled' => true,
        'upload_throttle' => [
            'enabled' => true,
            'max_attempts' => 30,
            'decay_minutes' => 1,
        ],
    ],

    'tenancy' => [
        'enabled' => null,
        'column' => 'tenant_id',
    ],

    'filament' => [
        'navigation_group' => null,
        'navigation_icon' => 'heroicon-o-photo',
        'navigation_sort' => null,
        'navigation_label' => 'Media Library',
    ],
];
