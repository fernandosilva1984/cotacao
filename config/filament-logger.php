<?php

return [
    'datetime_format' => 'd/m/Y H:i:s',
    'date_format' => 'd/m/Y',

    'activity_resource' => \App\Filament\Admin\Resources\ActivityResource::class,
    //'activity_resource' => \Jacobtims\FilamentLogger\Resources\ActivityResource::class,
    'scoped_to_tenant' => true,
    'navigation_sort' => 5,

    'resources' => [
        'enabled' => true,
        'log_name' => 'Resource',
        'logger' => \Jacobtims\FilamentLogger\Loggers\ResourceLogger::class,
        'color' => 'success',

        'exclude' => [
            // App\Filament\Resources\UserResource::class,
        ],
        'cluster' => null,
        'navigation_group' => 'Administração',
       // 'authorize' => true,
    ],

    'access' => [
        'enabled' => true,
        'logger' => \Jacobtims\FilamentLogger\Loggers\AccessLogger::class,
        'color' => 'danger',
        'log_name' => 'Access',
        //'authorize' => true,
    ],

    'notifications' => [
        'enabled' => true,
        'logger' => \Jacobtims\FilamentLogger\Loggers\NotificationLogger::class,
        'color' => null,
        'log_name' => 'Notification',
        //'authorize' => true,
    ],

    'models' => [
        'enabled' => true,
        'log_name' => 'Model',
        'color' => 'warning',
        'logger' => \Jacobtims\FilamentLogger\Loggers\ModelLogger::class,
        'register' => [
            // App\Models\User::class,
        ],
       // 'authorize' => true,
    ],

    'custom' => [
        // [
        //     'log_name' => 'Custom',
        //     'color' => 'primary',
        // ]
    ],
];